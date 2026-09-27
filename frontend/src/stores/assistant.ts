import { ref, watch } from 'vue'
import { defineStore } from 'pinia'
import { apiFetch, ApiError } from '@/lib/api'
import { enrichUmkm, umkmFromApi, type EnrichedUmkm } from './umkm'

/**
 * Chat with the NearBy assistant (`POST /api/assistant/chat`).
 *
 * The server searches the real catalogue and keeps no memory of its own: the
 * conversation state is the `context` it returns, which is sent back with the
 * next message. That is what makes "yang murah" refine "carikan makanan".
 * Context and messages live in sessionStorage, so a page refresh keeps the
 * conversation while a new tab starts clean.
 */

export interface ChatMessage {
  role: 'user' | 'assistant'
  text: string
  results?: EnrichedUmkm[]
  total?: number
  error?: boolean
}

interface ChatResponse {
  reply: string
  context: Record<string, unknown>
  results: any[]
  total: number
  detailId: number | null
  source: 'rules' | 'ai'
}

const STORAGE_KEY = 'nearby_assistant'
/** Turns of history sent along, for the optional wording layer. */
const HISTORY_TURNS = 8

function load(): { messages: ChatMessage[]; context: Record<string, unknown> } {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (raw) {
      const parsed = JSON.parse(raw)
      if (Array.isArray(parsed.messages)) return { messages: parsed.messages, context: parsed.context ?? {} }
    }
  } catch {
    // Unavailable or corrupt storage - start a fresh conversation.
  }
  return { messages: [], context: {} }
}

export const useAssistantStore = defineStore('assistant', () => {
  const initial = load()
  const open = ref(false)
  const messages = ref<ChatMessage[]>(initial.messages)
  const context = ref<Record<string, unknown>>(initial.context)
  const sending = ref(false)

  watch(
    [messages, context],
    () => {
      try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ messages: messages.value.slice(-40), context: context.value }))
      } catch {
        // Storage full or blocked - the chat still works for this page view.
      }
    },
    { deep: true },
  )

  /** Ask one question; resolves with the assistant's reply (also appended to `messages`). */
  async function ask(text: string): Promise<ChatResponse | null> {
    const message = text.trim()
    if (!message || sending.value) return null

    const history = messages.value
      .filter((m) => !m.error)
      .slice(-HISTORY_TURNS)
      .map((m) => ({ role: m.role, text: m.text }))

    messages.value.push({ role: 'user', text: message })
    sending.value = true
    try {
      const res = await apiFetch<ChatResponse>('/assistant/chat', {
        method: 'POST',
        body: JSON.stringify({ message, context: context.value, history }),
      })
      context.value = res.context
      messages.value.push({
        role: 'assistant',
        text: res.reply,
        results: res.results.map((row) => enrichUmkm(umkmFromApi(row))),
        total: res.total,
      })
      return res
    } catch (e) {
      messages.value.push({
        role: 'assistant',
        text: e instanceof ApiError && e.status !== 0 ? e.firstError : 'Asisten tidak bisa dihubungi. Periksa koneksi internetmu lalu coba lagi.',
        error: true,
      })
      return null
    } finally {
      sending.value = false
    }
  }

  function reset() {
    messages.value = []
    context.value = {}
  }

  return { open, messages, context, sending, ask, reset }
})
