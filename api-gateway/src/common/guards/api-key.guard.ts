import {
  CanActivate,
  ExecutionContext,
  Injectable,
  UnauthorizedException,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { Request } from 'express';
import { timingSafeEqual } from 'crypto';

@Injectable()
export class ApiKeyGuard implements CanActivate {
  private readonly expected: Buffer;

  constructor(config: ConfigService) {
    this.expected = Buffer.from(config.getOrThrow<string>('INTERNAL_API_KEY'));
  }

  canActivate(context: ExecutionContext): boolean {
    const provided = context
      .switchToHttp()
      .getRequest<Request>()
      .header('x-api-key');

    const candidate = Buffer.from(provided ?? '');
    if (
      candidate.length !== this.expected.length ||
      !timingSafeEqual(candidate, this.expected)
    ) {
      throw new UnauthorizedException('Invalid API key');
    }
    return true;
  }
}
