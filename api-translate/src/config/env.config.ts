import path from 'node:path';
import dotenv from 'dotenv';
import { z } from 'zod';

dotenv.config();

const envSchema = z.object({
  NODE_ENV: z.enum(['development', 'production', 'test']).default('development'),
  PORT: z.coerce.number().int().positive().default(3000),
  HOST: z.string().default('0.0.0.0'),
  API_PREFIX: z.string().default('/api/v1'),

  CORS_ORIGINS: z.string().default('*'),

  RATE_LIMIT_WINDOW_MS: z.coerce.number().int().positive().default(60_000),
  RATE_LIMIT_MAX_REQUESTS: z.coerce.number().int().positive().default(60),

  MODELS_CACHE_DIR: z.string().default('./models_cache'),
  MODEL_MAX_INPUT_CHARS: z.coerce.number().int().positive().default(5000),
  TRANSLATION_TIMEOUT_MS: z.coerce.number().int().positive().default(30_000),
  PRELOAD_LANGUAGE_PAIRS: z.string().default(''),

  LOG_LEVEL: z.enum(['error', 'warn', 'info', 'http', 'debug']).default('info'),
  LOG_DIR: z.string().default('./logs'),
});

const parsed = envSchema.safeParse(process.env);

if (!parsed.success) {
  // eslint-disable-next-line no-console
  console.error('Invalid environment configuration:', parsed.error.flatten().fieldErrors);
  process.exit(1);
}

const env = parsed.data;

export const config = {
  env: env.NODE_ENV,
  isProduction: env.NODE_ENV === 'production',
  isDevelopment: env.NODE_ENV === 'development',
  isTest: env.NODE_ENV === 'test',

  server: {
    port: env.PORT,
    host: env.HOST,
    apiPrefix: env.API_PREFIX,
  },

  cors: {
    origins: env.CORS_ORIGINS === '*' ? '*' : env.CORS_ORIGINS.split(',').map((o) => o.trim()),
  },

  rateLimit: {
    windowMs: env.RATE_LIMIT_WINDOW_MS,
    maxRequests: env.RATE_LIMIT_MAX_REQUESTS,
  },

  models: {
    cacheDir: path.resolve(process.cwd(), env.MODELS_CACHE_DIR),
    maxInputChars: env.MODEL_MAX_INPUT_CHARS,
    translationTimeoutMs: env.TRANSLATION_TIMEOUT_MS,
    preloadPairs: env.PRELOAD_LANGUAGE_PAIRS.split(',')
      .map((p) => p.trim())
      .filter(Boolean),
  },

  logging: {
    level: env.LOG_LEVEL,
    dir: path.resolve(process.cwd(), env.LOG_DIR),
  },
} as const;

export type AppConfig = typeof config;
