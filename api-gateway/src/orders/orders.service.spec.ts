import { BadRequestException, ConflictException, HttpException, HttpStatus, Logger } from '@nestjs/common';
import { getQueueToken } from '@nestjs/bullmq';
import { Test } from '@nestjs/testing';
import { getDataSourceToken, getRepositoryToken } from '@nestjs/typeorm';
import { AuthUser } from '../common/interfaces/auth-user.interface';
import { Inventory } from '../products/entities/inventory.entity';
import { ProductsService } from '../products/products.service';
import { UserRole } from '../users/entities/user.entity';
import { Order, OrderStatus, PaymentStatus } from './entities/order.entity';
import { MAIL_QUEUE, ORDER_CONFIRMATION_JOB } from './mail/mail.constants';
import { OrdersService } from './orders.service';
import { PaymentService } from './payment/payment.service';

const customer: AuthUser = { id: '7', email: 'c@example.com', name: 'Cust', role: UserRole.CUSTOMER };

describe('OrdersService', () => {
  let service: OrdersService;
  let lockedInventory: Inventory[];

  const lockQuery = {
    setLock: jest.fn().mockReturnThis(),
    where: jest.fn().mockReturnThis(),
    orderBy: jest.fn().mockReturnThis(),
    getMany: jest.fn(() => Promise.resolve(lockedInventory)),
  };
  const manager = {
    find: jest.fn(),
    createQueryBuilder: jest.fn(() => lockQuery),
    create: jest.fn((_entity: unknown, data: object) => ({ ...data })),
    save: jest.fn((entity: object | object[]) =>
      Promise.resolve(
        Array.isArray(entity)
          ? entity.map((e, i) => ({ id: String(i + 1), ...e }))
          : { id: '500', createdAt: new Date(), ...entity },
      ),
    ),
    update: jest.fn(),
  };
  const dataSource = { transaction: jest.fn((cb: (m: typeof manager) => unknown) => cb(manager)) };
  const queue = { add: jest.fn() };
  const payments = { charge: jest.fn() };
  const products = { invalidateCache: jest.fn() };

  beforeEach(async () => {
    jest.clearAllMocks();
    manager.find.mockResolvedValue([
      { id: '1', name: 'Shirt', price: '19.99', isActive: true },
      { id: '2', name: 'Hat', price: '5.00', isActive: true },
    ]);
    lockedInventory = [
      { productId: '1', quantity: 10 } as Inventory,
      { productId: '2', quantity: 1 } as Inventory,
    ];
    payments.charge.mockResolvedValue({ success: true, reference: 'pay_123' });

    const moduleRef = await Test.createTestingModule({
      providers: [
        OrdersService,
        { provide: getDataSourceToken(), useValue: dataSource },
        { provide: getRepositoryToken(Order), useValue: {} },
        { provide: getQueueToken(MAIL_QUEUE), useValue: queue },
        { provide: PaymentService, useValue: payments },
        { provide: ProductsService, useValue: products },
      ],
    }).compile();

    service = moduleRef.get(OrdersService);
  });

  it('creates a paid order, decrements locked inventory and queues the confirmation email', async () => {
    const result = await service.create(customer, {
      items: [
        { productId: 1, quantity: 2 },
        { productId: 2, quantity: 1 },
        { productId: 1, quantity: 1 },
      ],
      paymentToken: 'tok_visa',
    });

    expect(lockQuery.setLock).toHaveBeenCalledWith('pessimistic_write');
    expect(payments.charge).toHaveBeenCalledWith(6497, 'tok_visa', expect.stringMatching(/^ORD-\d{8}-[0-9A-F]{8}$/));

    expect(manager.update).toHaveBeenCalledWith(Inventory, { productId: '1' }, { quantity: 7 });
    expect(manager.update).toHaveBeenCalledWith(Inventory, { productId: '2' }, { quantity: 0 });

    expect(result).toMatchObject({
      id: 500,
      status: OrderStatus.PAID,
      paymentStatus: PaymentStatus.PAID,
      paymentReference: 'pay_123',
      total: 64.97,
    });
    expect(result.items).toEqual([
      expect.objectContaining({ productId: 1, quantity: 3, unitPrice: 19.99, lineTotal: 59.97 }),
      expect.objectContaining({ productId: 2, quantity: 1, unitPrice: 5, lineTotal: 5 }),
    ]);

    expect(queue.add).toHaveBeenCalledWith(ORDER_CONFIRMATION_JOB, { orderId: '500' }, expect.any(Object));
    expect(products.invalidateCache).toHaveBeenCalled();
  });

  it('rejects the order when stock is insufficient, before charging', async () => {
    await expect(
      service.create(customer, { items: [{ productId: 2, quantity: 3 }], paymentToken: 'tok_visa' }),
    ).rejects.toBeInstanceOf(ConflictException);

    expect(payments.charge).not.toHaveBeenCalled();
    expect(manager.save).not.toHaveBeenCalled();
    expect(queue.add).not.toHaveBeenCalled();
  });

  it('rejects unknown or inactive products', async () => {
    manager.find.mockResolvedValue([]);

    await expect(
      service.create(customer, { items: [{ productId: 9, quantity: 1 }], paymentToken: 'tok_visa' }),
    ).rejects.toBeInstanceOf(BadRequestException);
    expect(manager.createQueryBuilder).not.toHaveBeenCalled();
  });

  it('returns 402 and persists nothing when payment fails', async () => {
    payments.charge.mockResolvedValue({ success: false, reference: null, failureReason: 'card_declined' });

    const error = await service
      .create(customer, { items: [{ productId: 1, quantity: 1 }], paymentToken: 'tok_fail' })
      .catch((e: unknown) => e);

    expect(error).toBeInstanceOf(HttpException);
    expect((error as HttpException).getStatus()).toBe(HttpStatus.PAYMENT_REQUIRED);
    expect(manager.save).not.toHaveBeenCalled();
    expect(manager.update).not.toHaveBeenCalled();
    expect(queue.add).not.toHaveBeenCalled();
  });

  it('still succeeds when the email queue is unavailable after commit', async () => {
    queue.add.mockRejectedValue(new Error('redis down'));
    const logError = jest.spyOn(Logger.prototype, 'error').mockImplementation();

    await expect(
      service.create(customer, { items: [{ productId: 1, quantity: 1 }], paymentToken: 'tok_visa' }),
    ).resolves.toMatchObject({ status: OrderStatus.PAID });
    expect(logError).toHaveBeenCalled();
    logError.mockRestore();
  });
});
