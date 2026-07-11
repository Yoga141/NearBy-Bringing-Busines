import type { Request, Response } from 'express';
import { languageDetectorService } from '@/services/language-detector.service';
import type { DetectSchema } from '@/validators/detect.validator';
import { asyncHandler } from '@/utils/async-handler.util';
import { sendSuccess } from '@/utils/response.util';

export const detectController = asyncHandler(async (req: Request, res: Response) => {
  const startTime = Date.now();
  const { text } = req.body as DetectSchema;

  const language = languageDetectorService.detect(text);

  sendSuccess(res, { language }, undefined, startTime);
});
