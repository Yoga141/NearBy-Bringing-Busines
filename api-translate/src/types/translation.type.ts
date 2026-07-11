import type { LanguageCode } from '@/constants/languages.constant';

export interface TranslationResult {
  from: LanguageCode;
  to: LanguageCode;
  translatedText: string;
  detectedLanguage?: LanguageCode;
  sourceText: string;
}

export interface TranslationEngine {
  translate(text: string, from: LanguageCode, to: LanguageCode): Promise<string>;
  preload(from: LanguageCode, to: LanguageCode): Promise<void>;
  isReady(from: LanguageCode, to: LanguageCode): boolean;
}
