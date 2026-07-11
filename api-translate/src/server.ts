import { createApp } from '@/app';
import { config } from '@/config/env.config';
import { LANGUAGE_MODEL_REGISTRY } from '@/models/language-model.registry';
import { modelManagerService } from '@/services/model-manager.service';
import { logger } from '@/utils/logger.util';

const start = async (): Promise<void> => {
  const app = createApp();

  const server = app.listen(config.server.port, config.server.host, () => {
    logger.info(`API Translate listening on http://${config.server.host}:${config.server.port}${config.server.apiPrefix}`, {
      env: config.env,
    });
  });

  if (config.models.preloadPairs.length > 0) {
    const modelIds = config.models.preloadPairs
      .map((pair) => LANGUAGE_MODEL_REGISTRY[pair]?.modelId)
      .filter((id): id is string => Boolean(id));

    logger.info('Preloading translation models', { pairs: config.models.preloadPairs });
    modelManagerService
      .preload(modelIds)
      .then(() => logger.info('All preloaded models are ready'))
      .catch((error: unknown) => logger.error('Failed to preload one or more models', { error }));
  }

  const shutdown = (signal: string): void => {
    logger.info(`Received ${signal}, shutting down gracefully...`);
    server.close(() => {
      logger.info('Server closed');
      process.exit(0);
    });
    setTimeout(() => process.exit(1), 10_000).unref();
  };

  process.on('SIGTERM', () => shutdown('SIGTERM'));
  process.on('SIGINT', () => shutdown('SIGINT'));
  process.on('unhandledRejection', (reason) => {
    logger.error('Unhandled promise rejection', { reason });
  });
  process.on('uncaughtException', (error) => {
    logger.error('Uncaught exception', { message: error.message, stack: error.stack });
    process.exit(1);
  });
};

start().catch((error) => {
  // eslint-disable-next-line no-console
  console.error('Failed to start server:', error);
  process.exit(1);
});
