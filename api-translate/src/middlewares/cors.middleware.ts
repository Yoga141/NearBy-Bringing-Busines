import cors, { type CorsOptions } from 'cors';
import { config } from '@/config/env.config';

const corsOptions: CorsOptions = {
  origin: (origin, callback) => {
    if (config.cors.origins === '*' || !origin) {
      callback(null, true);
      return;
    }
    if (config.cors.origins.includes(origin)) {
      callback(null, true);
      return;
    }
    callback(new Error(`Origin "${origin}" is not allowed by CORS policy`));
  },
  methods: ['GET', 'POST', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization', 'X-Request-Id'],
  exposedHeaders: ['X-Request-Id'],
  maxAge: 86_400,
};

export const corsMiddleware = cors(corsOptions);
