import { Injectable, Logger } from '@nestjs/common';
import { randomUUID } from 'crypto';

export interface PaymentResult {
  success: boolean;
  reference: string | null;
  failureReason?: string;
}

export const DECLINED_TOKEN = 'tok_fail';

@Injectable()
export class PaymentService {
  private readonly logger = new Logger(PaymentService.name);

  async charge(amountCents: number, token: string, orderNumber: string): Promise<PaymentResult> {
    if (amountCents <= 0) {
      return { success: false, reference: null, failureReason: 'invalid_amount' };
    }
    if (token === DECLINED_TOKEN) {
      this.logger.warn(`Payment declined for ${orderNumber}`);
      return { success: false, reference: null, failureReason: 'card_declined' };
    }

    const reference = `pay_${randomUUID()}`;
    this.logger.log(`Charged ${(amountCents / 100).toFixed(2)} for ${orderNumber} (${reference})`);
    return { success: true, reference };
  }
}
