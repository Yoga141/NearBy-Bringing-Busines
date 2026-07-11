import rateLimit from 'express-rate-limit';
import type { Request, Response } from 'express';
import { config } from '@/config/env.config';
import { ErrorCode } from '@/constants/error-code.constant';
import { HttpStatus } from '@/constants/http-status.constant';
import { sendError } from '@/utils/response.util';

export const rateLimitMiddleware = rateLimit({
  windowMs: config.rateLimit.windowMs,
  limit: config.rateLimit.maxRequests,
  standardHeaders: true,
  legacyHeaders: false,
  handler: (_req: Request, res: Response) => {
    sendError(
      res,
      HttpStatus.TOO_MANY_REQUESTS,
      ErrorCode.RATE_LIMIT_EXCEEDED,
      'Too many requests. Please slow down and try again later.',
    );
  },
});
