import { Product } from './entities/product.entity';

export interface ProductResponse {
  id: number;
  sku: string;
  name: string;
  slug: string;
  description: string | null;
  price: number;
  isActive: boolean;
  imageUrl: string | null;
  thumbnailUrl: string | null;
  category: { id: number; name: string; slug: string } | null;
  stock: { quantity: number; inStock: boolean };
  createdAt: Date | null;
  updatedAt: Date | null;
}

const publicUrl = (path: string | null) => (path ? `/storage/${path}` : null);

export function toProductResponse(product: Product): ProductResponse {
  const quantity = product.inventory?.quantity ?? 0;
  return {
    id: Number(product.id),
    sku: product.sku,
    name: product.name,
    slug: product.slug,
    description: product.description,
    price: Number(product.price),
    isActive: product.isActive,
    imageUrl: publicUrl(product.imagePath),
    thumbnailUrl: publicUrl(product.thumbnailPath),
    category: product.category
      ? {
          id: Number(product.category.id),
          name: product.category.name,
          slug: product.category.slug,
        }
      : null,
    stock: { quantity, inStock: quantity > 0 },
    createdAt: product.createdAt,
    updatedAt: product.updatedAt,
  };
}
