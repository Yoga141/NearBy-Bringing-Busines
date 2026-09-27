/**
 * Features that exist in the code but are switched off for users until they
 * are ready. With a flag off, nothing of the feature is mounted: no button,
 * no floating widget, no microphone, no request to its API.
 *
 * To bring one back, set it to true and rebuild (`npm run build`). The chat
 * assistant also needs ASSISTANT_ENABLED=true in the backend .env, since the
 * API refuses the endpoint while it is off.
 */
export const FEATURES = {
  /** Accessibility panel, read-aloud, on-screen keyboard, voice assistant ("Oke NearBy"). */
  accessibility: false,
  /** "Tanya Asisten" chat (POST /api/assistant/chat). */
  assistant: false,
} as const
