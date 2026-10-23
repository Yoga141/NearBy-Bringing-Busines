/**
 * Turns one transcript into something `stores/voice.ts` can act on, using the
 * in-browser rules in `voiceIntent.ts`. Adds the two answers that only exist
 * around the wake word on top of `parseCommand`.
 */
import type { CategoryName, LocationName } from '@/types'
import { parseCommand, stripWakeWord, type PageName, type VoiceIntent } from './voiceIntent'

/** `VoiceIntent` plus the two answers that only exist around the wake word. */
export type VoiceAction = VoiceIntent | 'siaga_perintah' | 'abaikan'

export interface VoiceCommand {
  /** What the store executes. */
  action: VoiceAction
  intent: string
  category: CategoryName | null
  location: LocationName | null
  page: PageName | null
  keyword: string
  index: number | null
  /** Sentence to read out right away; empty when the store words its own reply. */
  message: string
  /** The command as understood, for "saya belum mengerti …". */
  raw: string
}

/**
 * Interpret one utterance. `requireWake` is true while the assistant is idle
 * ("siaga"): anything without "Oke NearBy" comes back as `abaikan`.
 */
export function interpret(text: string, requireWake: boolean): VoiceCommand {
  const base = { category: null, location: null, page: null, keyword: '', index: null, message: '', raw: '' }
  let command = text
  if (requireWake) {
    const afterWake = stripWakeWord(text)
    if (afterWake === null) return { ...base, action: 'abaikan', intent: 'abaikan' }
    if (!afterWake) return { ...base, action: 'siaga_perintah', intent: 'siaga_perintah', message: 'Ya, silakan.' }
    command = afterWake
  }
  const parsed = parseCommand(command)
  return { ...parsed, action: parsed.intent, intent: parsed.intent, message: '' }
}
