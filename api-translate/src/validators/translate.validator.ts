import { z } from 'zod';
import { config } from '@/config/env.config';
import { SUPPORTED_LANGUAGES } from '@/constants/languages.constant';

export const translateSchema = z.object({
  text: z
    .string({ required_error: 'The "text" field is required.', invalid_type_error: '"text" must be a string.' })
    .min(1, 'The "text" field must not be empty.')
    .max(config.models.maxInputChars, `"text" must not exceed ${config.models.maxInputChars} characters.`),
  from: z
    .string()
    .trim()
    .toLowerCase()
    .refine((v) => (SUPPORTED_LANGUAGES as readonly string[]).includes(v), {
      message: `"from" must be one of: ${SUPPORTED_LANGUAGES.join(', ')}`,
    })
    .optional(),
  to: z
    .string({ required_error: 'The "to" field is required.' })
    .trim()
    .toLowerCase()
    .refine((v) => (SUPPORTED_LANGUAGES as readonly string[]).includes(v), {
      message: `"to" must be one of: ${SUPPORTED_LANGUAGES.join(', ')}`,
    }),
});

export type TranslateSchema = z.infer<typeof translateSchema>;
