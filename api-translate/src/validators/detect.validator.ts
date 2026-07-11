import { z } from 'zod';
import { config } from '@/config/env.config';

export const detectSchema = z.object({
  text: z
    .string({ required_error: 'The "text" field is required.' })
    .min(1, 'The "text" field must not be empty.')
    .max(config.models.maxInputChars, `"text" must not exceed ${config.models.maxInputChars} characters.`),
});

export type DetectSchema = z.infer<typeof detectSchema>;
