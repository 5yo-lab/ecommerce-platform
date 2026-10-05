import { NestFactory } from '@nestjs/core';
import { ValidationPipe } from '@nestjs/common';
import { DocumentBuilder, SwaggerModule } from '@nestjs/swagger';
import cookieParser from 'cookie-parser';
import { AppModule } from './app.module';
import { AllExceptionsFilter } from './common/filters/all-exceptions.filter';

async function bootstrap() {
  const app = await NestFactory.create(AppModule);

  app.setGlobalPrefix('api');
  app.enableCors({ origin: true, credentials: true });
  app.use(cookieParser());
  app.useGlobalPipes(
    new ValidationPipe({
      whitelist: true,
      forbidNonWhitelisted: true,
      transform: true,
    }),
  );
  app.useGlobalFilters(new AllExceptionsFilter());

  const swaggerConfig = new DocumentBuilder()
    .setTitle('E-commerce API Gateway')
    .setDescription('Customer-facing REST API (products, orders, auth)')
    .setVersion('1.0')
    .addServer('/', 'Current host')
    .addBearerAuth()
    .addCookieAuth('access_token')
    .addApiKey({ type: 'apiKey', name: 'X-API-Key', in: 'header' }, 'api-key')
    .build();
  SwaggerModule.setup(
    'docs',
    app,
    SwaggerModule.createDocument(app, swaggerConfig),
    {
      useGlobalPrefix: true,
      swaggerOptions: {
        persistAuthorization: true,
        withCredentials: true,
        requestInterceptor: (req: { credentials?: string }) => {
          req.credentials = 'include';

          return req;
        },
      },
    },
  );

  await app.listen(process.env.PORT ?? 3000, '0.0.0.0');
}

bootstrap();
