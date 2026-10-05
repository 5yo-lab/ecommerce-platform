import { Logger } from '@nestjs/common';
import { Processor, WorkerHost } from '@nestjs/bullmq';
import { InjectRepository } from '@nestjs/typeorm';
import { Job } from 'bullmq';
import { Repository } from 'typeorm';
import { Order } from '../entities/order.entity';
import { MailService } from './mail.service';
import { MAIL_QUEUE, OrderConfirmationJobData } from './mail.constants';

@Processor(MAIL_QUEUE)
export class OrderConfirmationProcessor extends WorkerHost {
  private readonly logger = new Logger(OrderConfirmationProcessor.name);

  constructor(
    @InjectRepository(Order) private readonly orders: Repository<Order>,
    private readonly mail: MailService,
  ) {
    super();
  }

  async process(job: Job<OrderConfirmationJobData>): Promise<void> {
    const order = await this.orders.findOne({
      where: { id: job.data.orderId },
      relations: { user: true, items: true },
    });
    if (!order) {
      this.logger.warn(`Order ${job.data.orderId} not found; skipping confirmation email`);
      return;
    }

    await this.mail.sendOrderConfirmation(order);
    this.logger.log(`Confirmation email sent for ${order.orderNumber} to ${order.user.email}`);
  }
}
