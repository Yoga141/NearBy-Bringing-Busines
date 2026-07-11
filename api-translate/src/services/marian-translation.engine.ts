import { config } from '@/config/env.config';
import { ErrorCode } from '@/constants/error-code.constant';
import { HttpStatus } from '@/constants/http-status.constant';
import type { LanguageCode } from '@/constants/languages.constant';
import { getModelIdForPair } from '@/models/language-model.registry';
import { modelManagerService, type ModelManagerService } from '@/services/model-manager.service';
import type { TranslationEngine } from '@/types/translation.type';
import { AppError } from '@/utils/app-error.util';

const withTimeout = async <T>(promise: Promise<T>, ms: number): Promise<T> => {
  let timer: NodeJS.Timeout;
  const timeout = new Promise<never>((_, reject) => {
    timer = setTimeout(() => reject(AppError.gatewayTimeout(ErrorCode.TRANSLATION_TIMEOUT, 'Translation timed out')), ms);
  });
  try {
    return await Promise.race([promise, timeout]);
  } finally {
    clearTimeout(timer!);
  }
};

/** Offline MarianMT/OPUS-MT engine running on transformers.js + ONNX Runtime. */
export class MarianTranslationEngine implements TranslationEngine {
  constructor(private readonly modelManager: ModelManagerService) {}

  private resolveModelId(from: LanguageCode, to: LanguageCode): string {
    const modelId = getModelIdForPair(from, to);
    if (!modelId) {
      throw AppError.badRequest(
        ErrorCode.UNSUPPORTED_LANGUAGE_PAIR,
        `No local translation model is registered for "${from}" -> "${to}".`,
      );
    }
    return modelId;
  }

  async translate(text: string, from: LanguageCode, to: LanguageCode): Promise<string> {
    if (text.trim().length === 0) return text;

    const modelId = this.resolveModelId(from, to);

    try {
      const translator = await this.modelManager.getPipeline(modelId);
      const output = await withTimeout(translator(text), config.models.translationTimeoutMs);
      const result = Array.isArray(output) ? output[0] : output;
      const translatedText = result?.translation_text;

      if (typeof translatedText !== 'string') {
        throw new Error('Translation model returned an unexpected output shape');
      }

      return translatedText;
    } catch (error) {
      if (error instanceof AppError) throw error;
      throw new AppError(
        ErrorCode.TRANSLATION_FAILED,
        `Failed to translate text from "${from}" to "${to}"`,
        HttpStatus.INTERNAL_SERVER_ERROR,
        { cause: error instanceof Error ? error.message : String(error) },
      );
    }
  }

  async preload(from: LanguageCode, to: LanguageCode): Promise<void> {
    const modelId = this.resolveModelId(from, to);
    await this.modelManager.getPipeline(modelId);
  }

  isReady(from: LanguageCode, to: LanguageCode): boolean {
    const modelId = getModelIdForPair(from, to);
    return modelId ? this.modelManager.isLoaded(modelId) : false;
  }
}

export const marianTranslationEngine = new MarianTranslationEngine(modelManagerService);
