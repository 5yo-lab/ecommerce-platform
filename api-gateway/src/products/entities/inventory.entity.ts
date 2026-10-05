import { Column, Entity, JoinColumn, OneToOne, PrimaryColumn } from 'typeorm';
import { Product } from './product.entity';

/** Table owned by Laravel migrations; one stock row per product. */
@Entity('inventory')
export class Inventory {
  @PrimaryColumn({ name: 'product_id', type: 'bigint', unsigned: true })
  productId: string;

  @OneToOne(() => Product, (product) => product.inventory, {
    onDelete: 'CASCADE',
  })
  @JoinColumn({ name: 'product_id' })
  product: Product;

  @Column({ type: 'int', unsigned: true, default: 0 })
  quantity: number;

  @Column({
    name: 'low_stock_threshold',
    type: 'int',
    unsigned: true,
    default: 10,
  })
  lowStockThreshold: number;

  @Column({
    name: 'updated_at',
    type: 'timestamp',
    default: () => 'CURRENT_TIMESTAMP',
    onUpdate: 'CURRENT_TIMESTAMP',
  })
  updatedAt: Date;
}
