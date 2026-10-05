import { Injectable } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { createTransport, Transporter } from 'nodemailer';
import { Order } from '../entities/order.entity';

@Injectable()
export class MailService {
  private readonly transporter: Transporter;
  private readonly from: string;

  constructor(config: ConfigService) {
    this.transporter = createTransport({
      host: config.getOrThrow<string>('MAIL_HOST'),
      port: config.get<number>('MAIL_PORT', 1025),
      secure: false,
    });
    this.from = config.get<string>('MAIL_FROM', 'Shop <no-reply@ecommerce.local>');
  }

  async sendOrderConfirmation(order: Order): Promise<void> {
    const lines = order.items.map(
      (item) => `${item.quantity} x ${item.productName} @ ${item.unitPrice} = ${item.lineTotal}`,
    );

    await this.transporter.sendMail({
      from: this.from,
      to: order.user.email,
      subject: `Order confirmation ${order.orderNumber}`,
      text: [
        `Hi ${order.user.name},`,
        '',
        `Thanks for your order ${order.orderNumber}.`,
        '',
        ...lines,
        '',
        `Total: ${order.total}`,
        `Payment reference: ${order.paymentReference ?? '-'}`,
      ].join('\n'),
      html: `<p>Hi ${escapeHtml(order.user.name)},</p>
<p>Thanks for your order <strong>${order.orderNumber}</strong>.</p>
<ul>${lines.map((l) => `<li>${escapeHtml(l)}</li>`).join('')}</ul>
<p><strong>Total: ${order.total}</strong></p>
<p>Payment reference: ${order.paymentReference ?? '-'}</p>`,
    });
  }
}

function escapeHtml(value: string): string {
  return value.replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);
}
