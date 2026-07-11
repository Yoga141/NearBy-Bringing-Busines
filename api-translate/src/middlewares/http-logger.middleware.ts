import morgan from 'morgan';
import { config } from '@/config/env.config';
import { httpLogStream } from '@/utils/logger.util';

morgan.token('id', (_req, res) => (res as unknown as { locals: { requestId: string } }).locals.requestId);

const format = config.isProduction
  ? ':id :method :url :status :res[content-length] - :response-time ms'
  : ':id :method :url :status :response-time ms';

export const httpLoggerMiddleware = morgan(format, { stream: httpLogStream });
