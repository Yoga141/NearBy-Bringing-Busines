import type { LanguageCode } from '@/constants/languages.constant';

/**
 * Maps a "from-to" pair to the model that serves it. To add a language,
 * register its pair(s) here with a Hugging Face model id -- no changes
 * needed anywhere else (routes/controllers/services read from this).
 */
export interface ModelRegistryEntry {
  modelId: string;
  label: string;
}

export const LANGUAGE_MODEL_REGISTRY: Record<string, ModelRegistryEntry> = {
  'id-en': { modelId: 'Xenova/opus-mt-id-en', label: 'Indonesian -> English (OPUS-MT / MarianMT)' },
  'en-id': { modelId: 'Xenova/opus-mt-en-id', label: 'English -> Indonesian (OPUS-MT / MarianMT)' },

  // Add more pairs as needed, e.g.:
  // 'en-ja': { modelId: 'Xenova/opus-mt-en-jap', label: 'English -> Japanese' },
  // 'ja-en': { modelId: 'Xenova/opus-mt-jap-en', label: 'Japanese -> English' },
};

export const getLanguagePairKey = (from: string, to: string): string => `${from}-${to}`;

export const isSupportedLanguagePair = (from: LanguageCode, to: LanguageCode): boolean =>
  getLanguagePairKey(from, to) in LANGUAGE_MODEL_REGISTRY;

export const getModelIdForPair = (from: LanguageCode, to: LanguageCode): string | undefined =>
  LANGUAGE_MODEL_REGISTRY[getLanguagePairKey(from, to)]?.modelId;

export const listModelRegistry = (): Array<{ pair: string } & ModelRegistryEntry> =>
  Object.entries(LANGUAGE_MODEL_REGISTRY).map(([pair, entry]) => ({ pair, ...entry }));
