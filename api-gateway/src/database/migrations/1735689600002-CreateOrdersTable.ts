import {
  MigrationInterface,
  QueryRunner,
  Table,
  TableForeignKey,
  TableIndex,
} from 'typeorm';

export class CreateOrdersTable1735689600002 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.createTable(
      new Table({
        name: 'orders',
        columns: [
          {
            name: 'id',
            type: 'bigint',
            unsigned: true,
            isPrimary: true,
            isGenerated: true,
            generationStrategy: 'increment',
          },
          {
            name: 'order_number',
            type: 'varchar',
            length: '50',
            isUnique: true,
          },
          { name: 'user_id', type: 'bigint', unsigned: true },
          {
            name: 'status',
            type: 'enum',
            enum: [
              'pending',
              'paid',
              'processing',
              'shipped',
              'delivered',
              'cancelled',
            ],
            default: "'pending'",
          },
          {
            name: 'payment_status',
            type: 'enum',
            enum: ['pending', 'paid', 'failed', 'refunded'],
            default: "'pending'",
          },
          {
            name: 'payment_reference',
            type: 'varchar',
            length: '255',
            isNullable: true,
          },
          {
            name: 'subtotal',
            type: 'decimal',
            precision: 10,
            scale: 2,
            default: '0.00',
          },
          {
            name: 'total',
            type: 'decimal',
            precision: 10,
            scale: 2,
            default: '0.00',
          },
          {
            name: 'created_at',
            type: 'timestamp',
            default: 'CURRENT_TIMESTAMP',
          },
          {
            name: 'updated_at',
            type: 'timestamp',
            default: 'CURRENT_TIMESTAMP',
            onUpdate: 'CURRENT_TIMESTAMP',
          },
        ],
      }),
      true,
    );

    await queryRunner.createForeignKey(
      'orders',
      new TableForeignKey({
        name: 'fk_orders_user_id',
        columnNames: ['user_id'],
        referencedTableName: 'users',
        referencedColumnNames: ['id'],
      }),
    );

    await queryRunner.createIndex(
      'orders',
      new TableIndex({
        name: 'idx_orders_status_created',
        columnNames: ['status', 'created_at'],
      }),
    );

    await queryRunner.createIndex(
      'orders',
      new TableIndex({
        name: 'idx_orders_user_id',
        columnNames: ['user_id'],
      }),
    );
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.dropTable('orders');
  }
}
