import { env as transformersEnv } from '@xenova/transformers';
import { config } from '@/config/env.config';

/** Points transformers.js at MODELS_CACHE_DIR instead of the OS temp/home directory. */
export const configureTransformersRuntime = (): void => {
  transformersEnv.cacheDir = config.models.cacheDir;
  transformersEnv.localModelPath = config.models.cacheDir;
  transformersEnv.allowRemoteModels = true; // needed for the one-time model download
  transformersEnv.allowLocalModels = true;
};
