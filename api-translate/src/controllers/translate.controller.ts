import type { Request, Response } from 'express';
import { translationService } from '@/services/translation.service';
import type { TranslateSchema } from '@/validators/translate.validator';
import { asyncHandler } from '@/utils/async-handler.util';
import { sendSuccess } from '@/utils/response.util';

export const translateController = asyncHandler(async (req: Request, res: Response) => {
  const startTime = Date.now();
  const { text, from, to } = req.body as TranslateSchema;

  const result = await translationService.translate(text, from, to);

  sendSuccess(
    res,
    {
      from: result.from,
      to: result.to,
      translatedText: result.translatedText,
      ...(result.detectedLanguage ? { detectedLanguage: result.detectedLanguage } : {}),
    },
    undefined,
    startTime,
  );
});
