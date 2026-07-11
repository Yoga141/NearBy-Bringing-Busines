import { Router } from 'express';
import { translateController } from '@/controllers/translate.controller';
import { validateBody } from '@/middlewares/validate.middleware';
import { translateSchema } from '@/validators/translate.validator';

const router = Router();

router.post('/translate', validateBody(translateSchema), translateController);

export default router;
