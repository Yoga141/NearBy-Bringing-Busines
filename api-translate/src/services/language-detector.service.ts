import { franc } from 'franc-min';
import { ErrorCode } from '@/constants/error-code.constant';
import { SUPPORTED_LANGUAGES, type LanguageCode } from '@/constants/languages.constant';
import { AppError } from '@/utils/app-error.util';
import { logger } from '@/utils/logger.util';

/** ISO 639-3 (franc) -> our ISO 639-1 language codes. */
const ISO_639_3_TO_1: Record<string, LanguageCode> = {
  ind: 'id',
  eng: 'en',
};

const FRANC_WHITELIST = Object.keys(ISO_639_3_TO_1);

export class LanguageDetectorService {
  detect(text: string): LanguageCode {
    const candidates = FRANC_WHITELIST.filter((iso3) =>
      SUPPORTED_LANGUAGES.includes(ISO_639_3_TO_1[iso3]),
    );

    const detected = franc(text, { only: candidates, minLength: 1 });

    if (detected === 'und' || !(detected in ISO_639_3_TO_1)) {
      logger.warn('Language auto-detection could not confidently determine language', {
        textPreview: text.slice(0, 80),
      });
      throw AppError.unprocessable(
        ErrorCode.LANGUAGE_DETECTION_FAILED,
        'Could not automatically detect the source language. Please specify the "from" parameter explicitly.',
      );
    }

    return ISO_639_3_TO_1[detected];
  }
}

export const languageDetectorService = new LanguageDetectorService();
