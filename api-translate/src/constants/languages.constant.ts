/**
 * Supported languages. To add one, register it here and add its model(s)
 * in `src/models/language-model.registry.ts`.
 */

export const SUPPORTED_LANGUAGES = ['id', 'en'] as const;

export type LanguageCode = (typeof SUPPORTED_LANGUAGES)[number];

export interface LanguageInfo {
  code: LanguageCode;
  name: string;
  nativeName: string;
}

export const LANGUAGE_INFO: Record<LanguageCode, LanguageInfo> = {
  id: { code: 'id', name: 'Indonesian', nativeName: 'Bahasa Indonesia' },
  en: { code: 'en', name: 'English', nativeName: 'English' },
};

export const isSupportedLanguage = (value: string): value is LanguageCode =>
  (SUPPORTED_LANGUAGES as readonly string[]).includes(value);
