import type { Request, Response } from 'express';
import { LANGUAGE_INFO, SUPPORTED_LANGUAGES } from '@/constants/languages.constant';
import { listModelRegistry } from '@/models/language-model.registry';
import { asyncHandler } from '@/utils/async-handler.util';
import { sendSuccess } from '@/utils/response.util';

export const listLanguagesController = asyncHandler(async (_req: Request, res: Response) => {
  sendSuccess(res, {
    languages: SUPPORTED_LANGUAGES.map((code) => LANGUAGE_INFO[code]),
    supportedPairs: listModelRegistry(),
  });
});
