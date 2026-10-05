import {
  BadRequestException,
  ConflictException,
  Inject,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { CACHE_MANAGER } from '@nestjs/cache-manager';
import { InjectDataSource, InjectRepository } from '@nestjs/typeorm';
import type { Cache } from 'cache-manager';
import { DataSource, Not, Repository } from 'typeorm';
import { Category } from './entities/category.entity';
import { Inventory } from './entities/inventory.entity';
import { Product } from './entities/product.entity';
import { CreateProductDto } from './dto/create-product.dto';
import { ProductSort, QueryProductsDto } from './dto/query-products.dto';
import { UpdateProductDto } from './dto/update-product.dto';
import { ProductResponse, toProductResponse } from './product.mapper';

export interface Paginated<T> {
  data: T[];
  meta: { page: number; limit: number; total: number; totalPages: number };
}

const VERSION_KEY = 'products:version';
const VERSION_TTL_MS = 30 * 24 * 60 * 60 * 1000;

export function slugify(value: string): string {
  return value
    .toLowerCase()
    .normalize('NFKD')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

const escapeLike = (value: string) => value.replace(/[\\%_]/g, '\\$&');

@Injectable()
export class ProductsService {
  constructor(
    @InjectRepository(Product) private readonly products: Repository<Product>,
    @InjectRepository(Category)
    private readonly categories: Repository<Category>,
    @InjectDataSource() private readonly dataSource: DataSource,
    @Inject(CACHE_MANAGER) private readonly cache: Cache,
  ) {}

  async findAll(query: QueryProductsDto): Promise<Paginated<ProductResponse>> {
    const key = await this.cacheKey('list', this.serializeQuery(query));
    const cached = await this.cache.get<Paginated<ProductResponse>>(key);
    if (cached) {
      return cached;
    }

    const qb = this.products
      .createQueryBuilder('product')
      .leftJoinAndSelect('product.category', 'category')
      .leftJoinAndSelect('product.inventory', 'inventory')
      .where('product.isActive = :active', { active: true });

    if (query.categoryId) {
      qb.andWhere('product.categoryId = :categoryId', {
        categoryId: query.categoryId,
      });
    }
    if (query.search) {
      qb.andWhere('(product.name LIKE :search OR product.sku LIKE :search)', {
        search: `%${escapeLike(query.search)}%`,
      });
    }
    if (query.minPrice !== undefined) {
      qb.andWhere('product.price >= :minPrice', { minPrice: query.minPrice });
    }
    if (query.maxPrice !== undefined) {
      qb.andWhere('product.price <= :maxPrice', { maxPrice: query.maxPrice });
    }
    if (query.inStock === true) {
      qb.andWhere('inventory.quantity > 0');
    } else if (query.inStock === false) {
      qb.andWhere('(inventory.quantity IS NULL OR inventory.quantity = 0)');
    }

    switch (query.sort) {
      case ProductSort.PRICE_ASC:
        qb.orderBy('product.price', 'ASC');
        break;
      case ProductSort.PRICE_DESC:
        qb.orderBy('product.price', 'DESC');
        break;
      case ProductSort.NAME:
        qb.orderBy('product.name', 'ASC');
        break;
      default:
        qb.orderBy('product.createdAt', 'DESC');
    }
    qb.addOrderBy('product.id', 'DESC');

    // Joins are to-one, so plain OFFSET/LIMIT is safe (no row multiplication)
    qb.offset((query.page - 1) * query.limit).limit(query.limit);

    const [rows, total] = await qb.getManyAndCount();
    const result: Paginated<ProductResponse> = {
      data: rows.map(toProductResponse),
      meta: {
        page: query.page,
        limit: query.limit,
        total,
        totalPages: Math.ceil(total / query.limit),
      },
    };

    await this.cache.set(key, result);
    return result;
  }

  async findOne(id: number): Promise<ProductResponse> {
    const key = await this.cacheKey('item', String(id));
    const cached = await this.cache.get<ProductResponse>(key);
    if (cached) {
      return cached;
    }

    const product = await this.products.findOne({
      where: { id: String(id), isActive: true },
      relations: { category: true, inventory: true },
    });
    if (!product) {
      throw new NotFoundException(`Product ${id} not found`);
    }

    const response = toProductResponse(product);
    await this.cache.set(key, response);
    return response;
  }

  async create(dto: CreateProductDto): Promise<ProductResponse> {
    await this.assertCategoryExists(dto.categoryId);
    const slug = dto.slug ?? (await this.uniqueSlug(dto.name, dto.sku));
    await this.assertUnique(dto.sku, slug);

    const productId = await this.dataSource.transaction(async (manager) => {
      const product = await manager.save(
        manager.create(Product, {
          categoryId: String(dto.categoryId),
          sku: dto.sku,
          name: dto.name,
          slug,
          description: dto.description ?? null,
          price: dto.price.toFixed(2),
          isActive: dto.isActive ?? true,
        }),
      );
      await manager.save(
        manager.create(Inventory, {
          productId: product.id,
          quantity: dto.quantity ?? 0,
          lowStockThreshold: dto.lowStockThreshold ?? 10,
        }),
      );
      return product.id;
    });

    await this.invalidateCache();
    return this.loadForAdmin(productId);
  }

  async update(id: number, dto: UpdateProductDto): Promise<ProductResponse> {
    const product = await this.products.findOne({
      where: { id: String(id) },
      relations: { inventory: true },
    });
    if (!product) {
      throw new NotFoundException(`Product ${id} not found`);
    }

    if (dto.categoryId !== undefined) {
      await this.assertCategoryExists(dto.categoryId);
    }
    await this.assertUnique(dto.sku, dto.slug, product.id);

    await this.dataSource.transaction(async (manager) => {
      if (dto.categoryId !== undefined) product.categoryId = String(dto.categoryId);
      if (dto.sku !== undefined) product.sku = dto.sku;
      if (dto.name !== undefined) product.name = dto.name;
      if (dto.slug !== undefined) product.slug = dto.slug;
      if (dto.description !== undefined) product.description = dto.description;
      if (dto.price !== undefined) product.price = dto.price.toFixed(2);
      if (dto.isActive !== undefined) product.isActive = dto.isActive;

      const { inventory, ...columns } = product;
      await manager.save(Product, manager.create(Product, columns));

      if (dto.quantity !== undefined || dto.lowStockThreshold !== undefined) {
        await manager.save(
          manager.create(Inventory, {
            productId: product.id,
            quantity: dto.quantity ?? inventory?.quantity ?? 0,
            lowStockThreshold:
              dto.lowStockThreshold ?? inventory?.lowStockThreshold ?? 10,
          }),
        );
      }
    });

    await this.invalidateCache();
    return this.loadForAdmin(product.id);
  }

  /**
   * Called by the Laravel admin webhook after a catalogue change. The shared database already holds
   * the new data, so syncing means dropping stale cache entries and re-warming the changed product.
   */
  async syncFromAdmin(productId: number): Promise<{ productId: number; cached: boolean }> {
    await this.invalidateCache();
    try {
      await this.findOne(productId);
      return { productId, cached: true };
    } catch (error) {
      if (error instanceof NotFoundException) {
        return { productId, cached: false };
      }
      throw error;
    }
  }

  async invalidateCache(): Promise<void> {
    await this.cache.set(VERSION_KEY, String(Date.now()), VERSION_TTL_MS);
  }

  /** All keys embed a version; bumping it invalidates every list/item entry without a key scan. */
  private async cacheKey(kind: 'list' | 'item', suffix: string): Promise<string> {
    let version = await this.cache.get<string>(VERSION_KEY);
    if (!version) {
      version = String(Date.now());
      await this.cache.set(VERSION_KEY, version, VERSION_TTL_MS);
    }
    return `products:${version}:${kind}:${suffix}`;
  }

  private serializeQuery(query: QueryProductsDto): string {
    const entries = Object.entries(query)
      .filter(([, value]) => value !== undefined && value !== '')
      .sort(([a], [b]) => a.localeCompare(b));
    return entries.map(([k, v]) => `${k}=${String(v)}`).join('&');
  }

  private async loadForAdmin(id: string): Promise<ProductResponse> {
    const product = await this.products.findOneOrFail({
      where: { id },
      relations: { category: true, inventory: true },
    });
    return toProductResponse(product);
  }

  private async assertCategoryExists(categoryId: number): Promise<void> {
    const exists = await this.categories.exist({
      where: { id: String(categoryId) },
    });
    if (!exists) {
      throw new BadRequestException(`Category ${categoryId} does not exist`);
    }
  }

  private async assertUnique(
    sku: string | undefined,
    slug: string | undefined,
    ignoreId?: string,
  ): Promise<void> {
    const idFilter = ignoreId ? { id: Not(ignoreId) } : {};
    if (
      sku &&
      (await this.products.exist({ where: { sku, ...idFilter }, withDeleted: true }))
    ) {
      throw new ConflictException(`SKU "${sku}" is already in use`);
    }
    if (
      slug &&
      (await this.products.exist({ where: { slug, ...idFilter }, withDeleted: true }))
    ) {
      throw new ConflictException(`Slug "${slug}" is already in use`);
    }
  }

  private async uniqueSlug(name: string, sku: string): Promise<string> {
    const base = slugify(name);
    const taken = await this.products.exist({
      where: { slug: base },
      withDeleted: true,
    });
    return taken ? `${base}-${slugify(sku)}` : base;
  }
}
