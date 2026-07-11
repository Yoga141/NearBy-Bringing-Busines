import { pipeline, type PipelineType } from '@xenova/transformers';
import { configureTransformersRuntime } from '@/config/transformers.config';
import { logger } from '@/utils/logger.util';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type TranslationPipeline = (text: string, options?: Record<string, unknown>) => Promise<any>;

configureTransformersRuntime();

/** Caches one transformers.js pipeline per model id so each model loads only once. */
export class ModelManagerService {
  private readonly pipelines = new Map<string, Promise<TranslationPipeline>>();

  private load(modelId: string): Promise<TranslationPipeline> {
    logger.info('Loading translation model', { modelId });
    const startedAt = Date.now();

    return pipeline('translation' as PipelineType, modelId, {
      quantized: true,
      progress_callback: (progress: { status: string; progress?: number }) => {
        if (progress.status === 'progress' && typeof progress.progress === 'number') {
          logger.debug('Model download progress', { modelId, progress: `${progress.progress.toFixed(1)}%` });
        }
      },
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
    }).then((loaded: any) => {
      logger.info('Translation model ready', { modelId, tookMs: Date.now() - startedAt });
      return loaded as TranslationPipeline;
    }) as Promise<TranslationPipeline>;
  }

  getPipeline(modelId: string): Promise<TranslationPipeline> {
    let pending = this.pipelines.get(modelId);
    if (!pending) {
      pending = this.load(modelId).catch((error: unknown) => {
        // Allow a future request to retry instead of permanently caching a failed load.
        this.pipelines.delete(modelId);
        throw error;
      });
      this.pipelines.set(modelId, pending);
    }
    return pending;
  }

  isLoaded(modelId: string): boolean {
    return this.pipelines.has(modelId);
  }

  async preload(modelIds: string[]): Promise<void> {
    await Promise.all(modelIds.map((id) => this.getPipeline(id)));
  }
}

export const modelManagerService = new ModelManagerService();
