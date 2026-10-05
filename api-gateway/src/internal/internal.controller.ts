import {
  Body,
  Controller,
  HttpCode,
  HttpStatus,
  Logger,
  Post,
  UseGuards,
} from '@nestjs/common';
import { ApiSecurity, ApiTags } from '@nestjs/swagger';
import { ApiKeyGuard } from '../common/guards/api-key.guard';
import { ProductsService } from '../products/products.service';
import { ProductSyncDto } from './dto/product-sync.dto';

@ApiTags('internal')
@ApiSecurity('api-key')
@UseGuards(ApiKeyGuard)
@Controller('internal')
export class InternalController {
  private readonly logger = new Logger(InternalController.name);

  constructor(private readonly products: ProductsService) {}

  @Post('products/sync')
  @HttpCode(HttpStatus.ACCEPTED)
  async syncProduct(@Body() dto: ProductSyncDto) {
    const result = await this.products.syncFromAdmin(dto.productId);
    this.logger.log(`Product ${dto.productId} ${dto.event} by admin; cache refreshed=${result.cached}`);
    return { ...result, event: dto.event };
  }
}
