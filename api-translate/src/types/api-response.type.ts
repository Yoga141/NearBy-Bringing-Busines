import type { ErrorCode } from '@/constants/error-code.constant';

export interface ApiResponseMeta {
  requestId: string;
  timestamp: string;
  durationMs?: number;
}

export type ApiSuccessResponse<T extends Record<string, unknown>> = {
  success: true;
} & T & {
    meta: ApiResponseMeta;
  };

export interface ApiErrorDetail {
  code: ErrorCode;
  message: string;
  details?: unknown;
}

export interface ApiErrorResponse {
  success: false;
  error: ApiErrorDetail;
  meta: ApiResponseMeta;
}

export type ApiResponse<T extends Record<string, unknown>> = ApiSuccessResponse<T> | ApiErrorResponse;
