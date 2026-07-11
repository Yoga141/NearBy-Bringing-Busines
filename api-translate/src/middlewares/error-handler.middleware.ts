import type { NextFunction, Request, Response } from 'express';
import { ZodError } from 'zod';
import { ErrorCode } from '@/constants/error-code.constant';
import { HttpStatus } from '@/constants/http-status.constant';
import { AppError } from '@/utils/app-error.util';
import { logger } from '@/utils/logger.util';
import { sendError } from '@/utils/response.util';

// Must be registered last, after all routes.
// eslint-disable-next-line @typescript-eslint/no-unused-vars
export const errorHandlerMiddleware = (err: unknown, req: Request, res: Response, _next: NextFunction): void => {
  if (err instanceof AppError) {
    if (err.statusCode >= 500) {
      logger.error(err.message, { code: err.code, details: err.details, stack: err.stack });
    } else {
      logger.warn(err.message, { code: err.code, details: err.details, path: req.originalUrl });
    }
    sendError(res, err.statusCode, err.code, err.message, err.details);
    return;
  }

  if (err instanceof ZodError) {
    sendError(
      res,
      HttpStatus.BAD_REQUEST,
      ErrorCode.VALIDATION_ERROR,
      'Request validation failed.',
      err.flatten().fieldErrors,
    );
    return;
  }

  const error = err instanceof Error ? err : new Error(String(err));
  logger.error('Unhandled error', { message: error.message, stack: error.stack, path: req.originalUrl });
  sendError(res, HttpStatus.INTERNAL_SERVER_ERROR, ErrorCode.INTERNAL_ERROR, 'An unexpected error occurred.');
};
