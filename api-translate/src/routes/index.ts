import { Router } from 'express';
import detectRoute from '@/routes/detect.route';
import healthRoute from '@/routes/health.route';
import languageRoute from '@/routes/language.route';
import translateRoute from '@/routes/translate.route';

const router = Router();

router.use(healthRoute);
router.use(languageRoute);
router.use(translateRoute);
router.use(detectRoute);

export default router;
