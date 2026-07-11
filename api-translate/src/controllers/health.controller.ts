import type { Request, Response } from 'express';
import { listModelRegistry } from '@/models/language-model.registry';
import { modelManagerService } from '@/services/model-manager.service';
import { asyncHandler } from '@/utils/async-handler.util';
import { sendSuccess } from '@/utils/response.util';

export const healthController = asyncHandler(async (_req: Request, res: Response) => {
  const models = listModelRegistry().map((entry) => ({
    ...entry,
    loaded: modelManagerService.isLoaded(entry.modelId),
  }));

  sendSuccess(res, {
    status: 'ok' as const,
    uptimeSeconds: Math.round(process.uptime()),
    models,
  });
});
