import { BadRequestException, ConflictException, NotFoundException } from '@nestjs/common';
import { CACHE_MANAGER } from '@nestjs/cache-manager';
import { Test } from '@nestjs/testing';
import { getDataSourceToken, getRepositoryToken } from '@nestjs/typeorm';
import { Category } from './entities/category.entity';
import { Product } from './entities/product.entity';
import { ProductSort, QueryProductsDto } from './dto/query-products.dto';
import { ProductsService, slugify } from './products.service';

const product = (overrides: Partial<Product> = {}): Product =>
  ({
    id: '1',
    categoryId: '2',
    sku: 'SKU-1',
    name: 'Blue Shirt',
    slug: 'blue-shirt',
    description: null,
    price: '19.99',
    isActive: true,
    imagePath: 'products/1.jpg',
    thumbnailPath: null,
    createdAt: new Date('2026-01-01'),
    updatedAt: new Date('2026-01-01'),
    deletedAt: null,
    category: { id: '2', name: 'Shirts', slug: 'shirts' },
    inventory: { productId: '1', quantity: 5, lowStockThreshold: 10 },
    ...overrides,
  }) as Product;

const query = (overrides: Partial<QueryProductsDto> = {}): QueryProductsDto =>
  Object.assign(new QueryProductsDto(), overrides);

function createQueryBuilderMock(rows: Product[], total: number) {
  const qb: Record<string, jest.Mock> = {};
  for (const method of [
    'leftJoinAndSelect',
    'where',
    'andWhere',
    'orderBy',
    'addOrderBy',
    'offset',
    'limit',
  ]) {
    qb[method] = jest.fn().mockReturnValue(qb);
  }
  qb.getManyAndCount = jest.fn().mockResolvedValue([rows, total]);
  return qb;
}

describe('ProductsService', () => {
  let service: ProductsService;
  let store: Map<string, unknown>;
  const cache = {
    get: jest.fn((key: string) => Promise.resolve(store.get(key))),
    set: jest.fn((key: string, value: unknown) => {
      store.set(key, value);
      return Promise.resolve(value);
    }),
  };
  const productsRepo = {
    createQueryBuilder: jest.fn(),
    findOne: jest.fn(),
    findOneOrFail: jest.fn(),
    exist: jest.fn(),
  };
  const categoriesRepo = { exist: jest.fn() };
  const manager = {
    create: jest.fn((_entity: unknown, data: object) => ({ ...data })),
    save: jest.fn((entity: { id?: string }) => Promise.resolve({ id: '99', ...entity })),
  };
  const dataSource = {
    transaction: jest.fn((cb: (m: typeof manager) => unknown) => cb(manager)),
  };

  beforeEach(async () => {
    jest.clearAllMocks();
    store = new Map();

    const moduleRef = await Test.createTestingModule({
      providers: [
        ProductsService,
        { provide: getRepositoryToken(Product), useValue: productsRepo },
        { provide: getRepositoryToken(Category), useValue: categoriesRepo },
        { provide: getDataSourceToken(), useValue: dataSource },
        { provide: CACHE_MANAGER, useValue: cache },
      ],
    }).compile();

    service = moduleRef.get(ProductsService);
  });

  describe('findAll', () => {
    it('applies filters, paginates and caches the mapped result', async () => {
      const qb = createQueryBuilderMock([product()], 31);
      productsRepo.createQueryBuilder.mockReturnValue(qb);

      const result = await service.findAll(
        query({
          page: 2,
          limit: 15,
          categoryId: 2,
          search: '50%_off',
          minPrice: 10,
          maxPrice: 50,
          inStock: true,
          sort: ProductSort.PRICE_ASC,
        }),
      );

      expect(qb.andWhere).toHaveBeenCalledWith('product.categoryId = :categoryId', { categoryId: 2 });
      expect(qb.andWhere).toHaveBeenCalledWith(
        '(product.name LIKE :search OR product.sku LIKE :search)',
        { search: '%50\\%\\_off%' },
      );
      expect(qb.andWhere).toHaveBeenCalledWith('product.price >= :minPrice', { minPrice: 10 });
      expect(qb.andWhere).toHaveBeenCalledWith('product.price <= :maxPrice', { maxPrice: 50 });
      expect(qb.andWhere).toHaveBeenCalledWith('inventory.quantity > 0');
      expect(qb.orderBy).toHaveBeenCalledWith('product.price', 'ASC');
      expect(qb.offset).toHaveBeenCalledWith(15);
      expect(qb.limit).toHaveBeenCalledWith(15);

      expect(result.meta).toEqual({ page: 2, limit: 15, total: 31, totalPages: 3 });
      expect(result.data[0]).toMatchObject({
        id: 1,
        price: 19.99,
        imageUrl: '/storage/products/1.jpg',
        stock: { quantity: 5, inStock: true },
        category: { id: 2, name: 'Shirts' },
      });
      expect(cache.set).toHaveBeenCalledWith(expect.stringContaining(':list:'), result);
    });

    it('returns the cached page without hitting the database', async () => {
      productsRepo.createQueryBuilder.mockReturnValue(createQueryBuilderMock([product()], 1));
      const first = await service.findAll(query());

      const second = await service.findAll(query());

      expect(second).toBe(first);
      expect(productsRepo.createQueryBuilder).toHaveBeenCalledTimes(1);
    });
  });

  describe('findOne', () => {
    it('throws NotFoundException for unknown or inactive products', async () => {
      productsRepo.findOne.mockResolvedValue(null);

      await expect(service.findOne(404)).rejects.toBeInstanceOf(NotFoundException);
      expect(productsRepo.findOne).toHaveBeenCalledWith(
        expect.objectContaining({ where: { id: '404', isActive: true } }),
      );
    });

    it('caches a found product', async () => {
      productsRepo.findOne.mockResolvedValue(product());

      await service.findOne(1);
      await service.findOne(1);

      expect(productsRepo.findOne).toHaveBeenCalledTimes(1);
    });
  });

  describe('create', () => {
    const dto = { categoryId: 2, sku: 'NEW-1', name: 'New Product', price: 10.5, quantity: 7 };

    it('rejects an unknown category', async () => {
      categoriesRepo.exist.mockResolvedValue(false);

      await expect(service.create(dto)).rejects.toBeInstanceOf(BadRequestException);
      expect(dataSource.transaction).not.toHaveBeenCalled();
    });

    it('rejects a duplicate SKU', async () => {
      categoriesRepo.exist.mockResolvedValue(true);
      productsRepo.exist.mockImplementation(({ where }: { where: { sku?: string } }) =>
        Promise.resolve(where.sku === 'NEW-1'),
      );

      await expect(service.create(dto)).rejects.toBeInstanceOf(ConflictException);
    });

    it('creates product and inventory in one transaction and invalidates the cache', async () => {
      categoriesRepo.exist.mockResolvedValue(true);
      productsRepo.exist.mockResolvedValue(false);
      productsRepo.findOneOrFail.mockResolvedValue(product({ id: '99', sku: 'NEW-1' }));
      store.set('products:version', 'old');

      const result = await service.create(dto);

      expect(dataSource.transaction).toHaveBeenCalledTimes(1);
      expect(manager.create).toHaveBeenCalledWith(
        Product,
        expect.objectContaining({ sku: 'NEW-1', slug: 'new-product', price: '10.50', isActive: true }),
      );
      expect(manager.create).toHaveBeenCalledWith(
        expect.anything(),
        { productId: '99', quantity: 7, lowStockThreshold: 10 },
      );
      expect(store.get('products:version')).not.toBe('old');
      expect(result.id).toBe(99);
    });
  });

  describe('syncFromAdmin', () => {
    it('invalidates the cache and re-warms the synced product and default list', async () => {
      store.set('products:version', 'old');
      productsRepo.findOne.mockResolvedValue(product());
      productsRepo.createQueryBuilder.mockReturnValue(createQueryBuilderMock([product()], 1));

      await expect(service.syncFromAdmin(1)).resolves.toEqual({ productId: 1, cached: true });
      expect(store.get('products:version')).not.toBe('old');
      expect(productsRepo.createQueryBuilder).toHaveBeenCalled();
    });

    it('reports cached=false when the product was deleted or deactivated', async () => {
      productsRepo.findOne.mockResolvedValue(null);

      await expect(service.syncFromAdmin(1)).resolves.toEqual({ productId: 1, cached: false });
    });
  });

  it('slugify produces URL-safe slugs', () => {
    expect(slugify('  Fancy Shoes -- 2026!  ')).toBe('fancy-shoes-2026');
  });
});
