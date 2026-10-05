import { Order } from './entities/order.entity';

export interface OrderResponse {
  id: number;
  orderNumber: string;
  status: string;
  paymentStatus: string;
  paymentReference: string | null;
  subtotal: number;
  total: number;
  items: {
    productId: number;
    productName: string;
    unitPrice: number;
    quantity: number;
    lineTotal: number;
  }[];
  createdAt: Date;
}

export function toOrderResponse(order: Order): OrderResponse {
  return {
    id: Number(order.id),
    orderNumber: order.orderNumber,
    status: order.status,
    paymentStatus: order.paymentStatus,
    paymentReference: order.paymentReference,
    subtotal: Number(order.subtotal),
    total: Number(order.total),
    items: (order.items ?? []).map((item) => ({
      productId: Number(item.productId),
      productName: item.productName,
      unitPrice: Number(item.unitPrice),
      quantity: item.quantity,
      lineTotal: Number(item.lineTotal),
    })),
    createdAt: order.createdAt,
  };
}
