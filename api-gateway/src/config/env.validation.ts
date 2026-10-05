import * as Joi from 'joi';

export const envValidationSchema = Joi.object({
  NODE_ENV: Joi.string()
    .valid('development', 'production', 'test')
    .default('development'),
  PORT: Joi.number().default(3000),

  DB_HOST: Joi.string().required(),
  DB_PORT: Joi.number().default(3306),
  DB_DATABASE: Joi.string().required(),
  DB_USERNAME: Joi.string().required(),
  DB_PASSWORD: Joi.string().allow('').required(),

  REDIS_HOST: Joi.string().required(),
  REDIS_PORT: Joi.number().default(6379),
  PRODUCT_CACHE_TTL: Joi.number().default(300),

  // HS256 needs a 256-bit key; the Laravel side (firebase/php-jwt) rejects shorter secrets.
  JWT_SECRET: Joi.string().min(32).required(),
  JWT_TTL: Joi.number().default(3600),
  JWT_ISSUER: Joi.string().default('ecommerce-platform'),
  INTERNAL_API_KEY: Joi.string().min(16).required(),

  MAIL_HOST: Joi.string().required(),
  MAIL_PORT: Joi.number().default(1025),
  MAIL_FROM: Joi.string().default('Shop <no-reply@ecommerce.local>'),
});
