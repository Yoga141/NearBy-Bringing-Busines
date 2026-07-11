import { Router } from 'express';
import { listLanguagesController } from '@/controllers/language.controller';

const router = Router();

router.get('/languages', listLanguagesController);

export default router;
