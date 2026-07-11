import { ErrorCode } from '@/constants/error-code.constant';
import { HttpStatus, type HttpStatusCode } from '@/constants/http-status.constant';

export class AppError extends Error {
  public readonly statusCode: HttpStatusCode;
  public readonly code: ErrorCode;
  public readonly details?: unknown;

  constructor(code: ErrorCode, message: string, statusCode: HttpStatusCode, details?: unknown) {
    super(message);
    this.name = 'AppError';
    this.code = code;
    this.statusCode = statusCode;
    this.details = details;
    Error.captureStackTrace(this, this.constructor);
  }

  static badRequest(code: ErrorCode, message: string, details?: unknown): AppError {
    return new AppError(code, message, HttpStatus.BAD_REQUEST, details);
  }

  static unprocessable(code: ErrorCode, message: string, details?: unknown): AppError {
    return new AppError(code, message, HttpStatus.UNPROCESSABLE_ENTITY, details);
  }

  static gatewayTimeout(code: ErrorCode, message: string): AppError {
    return new AppError(code, message, HttpStatus.GATEWAY_TIMEOUT);
  }
}
