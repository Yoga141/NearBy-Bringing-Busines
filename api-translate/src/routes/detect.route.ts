import { Router } from 'express';
import { detectController } from '@/controllers/detect.controller';
import { validateBody } from '@/middlewares/validate.middleware';
import { detectSchema } from '@/validators/detect.validator';

const router = Router();

router.post('/detect', validateBody(detectSchema), detectController);

export default router;
