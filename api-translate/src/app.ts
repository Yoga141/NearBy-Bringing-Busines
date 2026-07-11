import compression from 'compression';
import express, { type Application } from 'express';
import helmet from 'helmet';
import { config } from '@/config/env.config';
import { corsMiddleware } from '@/middlewares/cors.middleware';
import { errorHandlerMiddleware } from '@/middlewares/error-handler.middleware';
import { httpLoggerMiddleware } from '@/middlewares/http-logger.middleware';
import { notFoundMiddleware } from '@/middlewares/not-found.middleware';
import { rateLimitMiddleware } from '@/middlewares/rate-limit.middleware';
import { requestIdMiddleware } from '@/middlewares/request-id.middleware';
import routes from '@/routes';

export const createApp = (): Application => {
  const app = express();

  app.disable('x-powered-by');
  app.set('trust proxy', 1);

  app.use(requestIdMiddleware);
  app.use(helmet());
  app.use(corsMiddleware);
  app.use(compression());
  app.use(express.json({ limit: '1mb' }));
  app.use(express.urlencoded({ extended: true, limit: '1mb' }));
  app.use(httpLoggerMiddleware);
  app.use(rateLimitMiddleware);

  app.use(config.server.apiPrefix, routes);

  app.use(notFoundMiddleware);
  app.use(errorHandlerMiddleware);

  return app;
};
