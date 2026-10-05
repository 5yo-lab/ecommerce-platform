export const MAIL_QUEUE = 'mail';
export const ORDER_CONFIRMATION_JOB = 'order-confirmation';

export interface OrderConfirmationJobData {
  orderId: string;
}
