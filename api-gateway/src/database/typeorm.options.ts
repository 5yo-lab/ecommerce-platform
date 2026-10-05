import { DataSourceOptions } from 'typeorm';
import { User } from '../users/entities/user.entity';
import { Order } from '../orders/entities/order.entity';
import { OrderItem } from '../orders/entities/order-item.entity';
import { Category } from '../products/entities/category.entity';
import { Product } from '../products/entities/product.entity';
import { Inventory } from '../products/entities/inventory.entity';

export const entities = [User, Order, OrderItem, Category, Product, Inventory];

export function buildTypeOrmOptions(
  env: Record<string, string | undefined> = process.env,
): DataSourceOptions {
  return {
    type: 'mysql',
    host: env.DB_HOST ?? 'mysql',
    port: parseInt(env.DB_PORT ?? '3306', 10),
    username: env.DB_USERNAME ?? 'ecommerce',
    password: env.DB_PASSWORD ?? 'secret',
    database: env.DB_DATABASE ?? 'ecommerce',
    timezone: 'Z',
    entities,
    migrations: [__dirname + '/migrations/*{.ts,.js}'],
    migrationsTableName: 'typeorm_migrations',
    synchronize: false,
  };
}
