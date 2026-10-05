import { Module } from '@nestjs/common';
import { BullModule } from '@nestjs/bullmq';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ProductsModule } from '../products/products.module';
import { OrderItem } from './entities/order-item.entity';
import { Order } from './entities/order.entity';
import { MAIL_QUEUE } from './mail/mail.constants';
import { MailService } from './mail/mail.service';
import { OrderConfirmationProcessor } from './mail/order-confirmation.processor';
import { OrdersController } from './orders.controller';
import { OrdersService } from './orders.service';
import { PaymentService } from './payment/payment.service';

@Module({
  imports: [
    TypeOrmModule.forFeature([Order, OrderItem]),
    BullModule.registerQueue({ name: MAIL_QUEUE }),
    ProductsModule,
  ],
  controllers: [OrdersController],
  providers: [OrdersService, PaymentService, MailService, OrderConfirmationProcessor],
})
export class OrdersModule {}
