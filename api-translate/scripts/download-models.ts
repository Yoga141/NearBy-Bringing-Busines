import { pipeline, type PipelineType } from '@xenova/transformers';
import { configureTransformersRuntime } from '../src/config/transformers.config';
import { listModelRegistry } from '../src/models/language-model.registry';

const main = async (): Promise<void> => {
  configureTransformersRuntime();

  const registry = listModelRegistry();
  console.log(`Downloading ${registry.length} translation model(s)...\n`);

  for (const { pair, modelId, label } of registry) {
    process.stdout.write(`[${pair}] ${label} (${modelId}) ... `);
    const start = Date.now();
    // eslint-disable-next-line no-await-in-loop
    await pipeline('translation' as PipelineType, modelId, { quantized: true });
    console.log(`done in ${((Date.now() - start) / 1000).toFixed(1)}s`);
  }

  console.log('\nAll models downloaded. The API can now run fully offline.');
};

main().catch((error) => {
  console.error('Model download failed:', error);
  process.exit(1);
});
