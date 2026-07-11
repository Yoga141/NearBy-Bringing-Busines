import type { Response } from 'express';
import type { ErrorCode } from '@/constants/error-code.constant';
import type { HttpStatusCode } from '@/constants/http-status.constant';
import { HttpStatus } from '@/constants/http-status.constant';
import type { ApiErrorResponse, ApiSuccessResponse } from '@/types/api-response.type';

const nowIso = (): string => new Date().toISOString();

export const sendSuccess = <T extends Record<string, unknown>>(
  res: Response,
  data: T,
  statusCode: HttpStatusCode = HttpStatus.OK,
  startTime?: number,
): void => {
  const requestId = res.locals.requestId as string;
  const body: ApiSuccessResponse<T> = {
    success: true,
    ...data,
    meta: {
      requestId,
      timestamp: nowIso(),
      ...(startTime ? { durationMs: Date.now() - startTime } : {}),
    },
  };
  res.status(statusCode).json(body);
};

export const sendError = (
  res: Response,
  statusCode: HttpStatusCode,
  code: ErrorCode,
  message: string,
  details?: unknown,
): void => {
  const requestId = res.locals.requestId as string;
  const body: ApiErrorResponse = {
    success: false,
    error: { code, message, ...(details !== undefined ? { details } : {}) },
    meta: { requestId, timestamp: nowIso() },
  };
  res.status(statusCode).json(body);
};
