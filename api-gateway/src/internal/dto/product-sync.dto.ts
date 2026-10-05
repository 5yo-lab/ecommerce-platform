import { ApiProperty } from '@nestjs/swagger';
import { IsIn, IsInt, Min } from 'class-validator';

export const PRODUCT_SYNC_EVENTS = ['created', 'updated', 'deleted', 'restored'] as const;

export class ProductSyncDto {
  @ApiProperty({ example: 1 })
  @IsInt()
  @Min(1)
  productId: number;

  @ApiProperty({ enum: PRODUCT_SYNC_EVENTS })
  @IsIn(PRODUCT_SYNC_EVENTS)
  event: (typeof PRODUCT_SYNC_EVENTS)[number];
}
