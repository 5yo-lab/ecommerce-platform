import {
  BadRequestException,
  ConflictException,
  HttpException,
  HttpStatus,
  Injectable,
  Logger,
  NotFoundException,
} from '@nestjs/common';
import { InjectQueue } from '@nestjs/bullmq';
import { InjectDataSource, InjectRepository } from '@nestjs/typeorm';
import { Queue } from 'bullmq';
import { randomBytes } from 'crypto';
import { DataSource, In, Repository } from 'typeorm';
import { AuthUser } from '../common/interfaces/auth-user.interface';
import { Inventory } from '../products/entities/inventory.entity';
import { Product } from '../products/entities/product.entity';
import { ProductsService } from '../products/products.service';
import { UserRole } from '../users/entities/user.entity';
import { CreateOrderDto } from './dto/create-order.dto';
import { QueryOrdersDto } from './dto/query-orders.dto';
import { OrderItem } from './entities/order-item.entity';
import { Order, OrderStatus, PaymentStatus } from './entities/order.entity';
import {
  MAIL_QUEUE,
  ORDER_CONFIRMATION_JOB,
  OrderConfirmationJobData,
} from './mail/mail.constants';
import { OrderResponse, toOrderResponse } from './order.mapper';
import { PaymentService } from './payment/payment.service';

const toCents = (amount: string | number) => Math.round(Number(amount) * 100);
const fromCents = (cents: number) => (cents / 100).toFixed(2);

@Injectable()
export class OrdersService {
  private readonly logger = new Logger(OrdersService.name);

  constructor(
    @InjectDataSource() private readonly dataSource: DataSource,
    @InjectRepository(Order) private readonly orders: Repository<Order>,
    @InjectQueue(MAIL_QUEUE)
    private readonly mailQueue: Queue<OrderConfirmationJobData>,
    private readonly payments: PaymentService,
    private readonly products: ProductsService,
  ) {}

  /**
   * 1. validate  2. check inventory  3. charge (mock)  4. persist + update inventory  5. queue email.
   * Steps 2-4 run in one transaction with the inventory rows locked, so concurrent orders cannot oversell;
   * the email is queued only after commit so it never announces an order that was rolled back.
   */
  async create(user: AuthUser, dto: CreateOrderDto): Promise<OrderResponse> {
    const requested = this.mergeItems(dto);
    const productIds = [...requested.keys()];

    const order = await this.dataSource.transaction(async (manager) => {
      const products = await manager.find(Product, {
        where: { id: In(productIds), isActive: true },
      });
      const missing = productIds.filter(
        (id) => !products.some((p) => String(p.id) === id),
      );
      if (missing.length > 0) {
        throw new BadRequestException(
          `Products not available: ${missing.join(', ')}`,
        );
      }

      const inventories = await manager
        .createQueryBuilder(Inventory, 'inventory')
        .setLock('pessimistic_write')
        .where('inventory.productId IN (:...ids)', { ids: productIds })
        .orderBy('inventory.productId', 'ASC')
        .getMany();

      const shortages = productIds
        .map((id) => {
          const available =
            inventories.find((inv) => String(inv.productId) === id)?.quantity ?? 0;
          const wanted = requested.get(id)!;
          return available < wanted
            ? `Product ${id}: requested ${wanted}, available ${available}`
            : null;
        })
        .filter((line): line is string => line !== null);
      if (shortages.length > 0) {
        throw new ConflictException(shortages);
      }

      const lines = productIds.map((id) => {
        const product = products.find((p) => String(p.id) === id)!;
        const quantity = requested.get(id)!;
        const unitCents = toCents(product.price);
        return { product, quantity, unitCents, lineCents: unitCents * quantity };
      });
      const totalCents = lines.reduce((sum, line) => sum + line.lineCents, 0);
      const orderNumber = this.generateOrderNumber();

      const payment = await this.payments.charge(
        totalCents,
        dto.paymentToken,
        orderNumber,
      );
      if (!payment.success) {
        throw new HttpException(
          `Payment failed: ${payment.failureReason ?? 'unknown'}`,
          HttpStatus.PAYMENT_REQUIRED,
        );
      }

      const saved = await manager.save(
        manager.create(Order, {
          orderNumber,
          userId: user.id,
          status: OrderStatus.PAID,
          paymentStatus: PaymentStatus.PAID,
          paymentReference: payment.reference,
          subtotal: fromCents(totalCents),
          total: fromCents(totalCents),
        }),
      );

      saved.items = await manager.save(
        lines.map((line) =>
          manager.create(OrderItem, {
            orderId: saved.id,
            productId: line.product.id,
            productName: line.product.name,
            unitPrice: fromCents(line.unitCents),
            quantity: line.quantity,
            lineTotal: fromCents(line.lineCents),
          }),
        ),
      );

      for (const inventory of inventories.filter((inv) => requested.has(String(inv.productId)))) {
        await manager.update(
          Inventory,
          { productId: inventory.productId },
          { quantity: inventory.quantity - requested.get(String(inventory.productId))! },
        );
      }

      return saved;
    });

    await this.afterCommit(order);
    return toOrderResponse(order);
  }

  async findForUser(
    user: AuthUser,
    query: QueryOrdersDto,
  ): Promise<{ data: OrderResponse[]; meta: Record<string, number> }> {
    const [rows, total] = await this.orders.findAndCount({
      where: { userId: user.id },
      relations: { items: true },
      order: { createdAt: 'DESC', id: 'DESC' },
      skip: (query.page - 1) * query.limit,
      take: query.limit,
    });
    return {
      data: rows.map(toOrderResponse),
      meta: {
        page: query.page,
        limit: query.limit,
        total,
        totalPages: Math.ceil(total / query.limit),
      },
    };
  }

  async findOneForUser(user: AuthUser, id: number): Promise<OrderResponse> {
    const order = await this.orders.findOne({
      where: { id: String(id) },
      relations: { items: true },
    });
    if (!order || (user.role !== UserRole.ADMIN && String(order.userId) !== user.id)) {
      throw new NotFoundException(`Order ${id} not found`);
    }
    return toOrderResponse(order);
  }

  private async afterCommit(order: Order): Promise<void> {
    await this.products.invalidateCache();
    try {
      await this.mailQueue.add(
        ORDER_CONFIRMATION_JOB,
        { orderId: String(order.id) },
        {
          attempts: 3,
          backoff: { type: 'exponential', delay: 5000 },
          removeOnComplete: true,
        },
      );
    } catch (error) {
      // The order is already committed; a queue outage must not turn a successful purchase into an error
      this.logger.error(
        `Failed to queue confirmation email for ${order.orderNumber}`,
        error instanceof Error ? error.stack : String(error),
      );
    }
  }

  private mergeItems(dto: CreateOrderDto): Map<string, number> {
    const merged = new Map<string, number>();
    for (const item of dto.items) {
      const id = String(item.productId);
      merged.set(id, (merged.get(id) ?? 0) + item.quantity);
    }
    return merged;
  }

  private generateOrderNumber(): string {
    const date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    return `ORD-${date}-${randomBytes(4).toString('hex').toUpperCase()}`;
  }
}
