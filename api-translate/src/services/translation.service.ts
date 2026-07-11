import { config } from '@/config/env.config';
import { ErrorCode } from '@/constants/error-code.constant';
import { isSupportedLanguage, type LanguageCode } from '@/constants/languages.constant';
import { isSupportedLanguagePair } from '@/models/language-model.registry';
import { languageDetectorService, type LanguageDetectorService } from '@/services/language-detector.service';
import { marianTranslationEngine } from '@/services/marian-translation.engine';
import type { TranslationEngine, TranslationResult } from '@/types/translation.type';
import { AppError } from '@/utils/app-error.util';
import { joinLines, splitIntoLines, splitIntoSentences } from '@/utils/text-chunker.util';
import { protectText, restoreProtectedText } from '@/utils/text-protector.util';

export class TranslationService {
  constructor(
    private readonly engine: TranslationEngine,
    private readonly detector: LanguageDetectorService,
  ) {}

  private assertValidLength(text: string): void {
    if (text.trim().length === 0) {
      throw AppError.badRequest(ErrorCode.EMPTY_TEXT, 'The "text" field must not be empty.');
    }
    if (text.length > config.models.maxInputChars) {
      throw AppError.badRequest(
        ErrorCode.TEXT_TOO_LONG,
        `Text exceeds the maximum allowed length of ${config.models.maxInputChars} characters.`,
        { maxLength: config.models.maxInputChars, receivedLength: text.length },
      );
    }
  }

  private assertSupportedLanguage(code: string, field: 'from' | 'to'): asserts code is LanguageCode {
    if (!isSupportedLanguage(code)) {
      throw AppError.badRequest(
        ErrorCode.UNSUPPORTED_LANGUAGE,
        `"${code}" is not a supported language for the "${field}" field.`,
      );
    }
  }

  private assertSupportedPair(from: LanguageCode, to: LanguageCode): void {
    if (!isSupportedLanguagePair(from, to)) {
      throw AppError.badRequest(
        ErrorCode.UNSUPPORTED_LANGUAGE_PAIR,
        `Translation from "${from}" to "${to}" is not supported yet.`,
      );
    }
  }

  private async translateLine(content: string, from: LanguageCode, to: LanguageCode): Promise<string> {
    if (content.trim().length === 0) return content;

    const chunks = splitIntoSentences(content);
    const translatedChunks: string[] = [];
    for (const chunk of chunks) {
      // eslint-disable-next-line no-await-in-loop -- sequential to avoid overlapping ONNX session calls
      const translated = await this.engine.translate(chunk, from, to);
      translatedChunks.push(translated);
    }
    return translatedChunks.join(' ').replace(/\s+/g, ' ').trim();
  }

  async translate(rawText: string, from: string | undefined, to: string): Promise<TranslationResult> {
    this.assertValidLength(rawText);
    this.assertSupportedLanguage(to, 'to');

    let sourceLanguage: LanguageCode;
    let detectedLanguage: LanguageCode | undefined;

    if (from) {
      this.assertSupportedLanguage(from, 'from');
      sourceLanguage = from;
    } else {
      detectedLanguage = this.detector.detect(rawText);
      sourceLanguage = detectedLanguage;
    }

    if (sourceLanguage === to) {
      return { from: sourceLanguage, to, translatedText: rawText, detectedLanguage, sourceText: rawText };
    }

    this.assertSupportedPair(sourceLanguage, to);

    const { text: protectedText, tokens } = protectText(rawText);
    const lines = splitIntoLines(protectedText);

    const translatedLines: string[] = [];
    for (const line of lines) {
      if (line.isBlank) {
        translatedLines.push(line.content);
        continue;
      }
      // eslint-disable-next-line no-await-in-loop -- sequential, see translateLine
      const translatedContent = await this.translateLine(line.content, sourceLanguage, to);
      translatedLines.push(line.marker + translatedContent);
    }

    const translatedFull = joinLines(translatedLines);
    const translatedText = restoreProtectedText(translatedFull, tokens);

    return { from: sourceLanguage, to, translatedText, detectedLanguage, sourceText: rawText };
  }
}

export const translationService = new TranslationService(marianTranslationEngine, languageDetectorService);
