import type { Request, Response } from 'express';
import { ErrorCode } from '@/constants/error-code.constant';
import { HttpStatus } from '@/constants/http-status.constant';
import { sendError } from '@/utils/response.util';

export const notFoundMiddleware = (req: Request, res: Response): void => {
  sendError(res, HttpStatus.NOT_FOUND, ErrorCode.NOT_FOUND, `Route ${req.method} ${req.originalUrl} not found`);
};
