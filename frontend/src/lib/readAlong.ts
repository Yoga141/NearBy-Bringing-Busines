/**
 * Read-along highlighting for "Bacakan isi layar".
 *
 * Uses the CSS Custom Highlight API, which paints ranges without touching the
 * DOM - important because Vue owns these nodes and wrapping words in <span>s
 * would fight its patching. Browsers without the API still read aloud, just
 * without the highlight.
 */

const SENTENCE = 'a11y-read-sentence'
const WORD = 'a11y-read-word'

const SKIP_TAGS = new Set(['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEMPLATE', 'SVG', 'TEXTAREA', 'SELECT'])

export const highlightSupported =
  typeof CSS !== 'undefined' && 'highlights' in CSS && typeof Highlight !== 'undefined'

export interface TextMap {
  /** Whitespace-collapsed visible text, in reading order. */
  text: string
  /** DOM position of the character at `index`, for building a Range. */
  locate(index: number): { node: Text; offset: number }
}

function isVisible(el: Element, cache: Map<Element, boolean>): boolean {
  const cached = cache.get(el)
  if (cached !== undefined) return cached
  let visible = !SKIP_TAGS.has(el.tagName.toUpperCase()) && el.getAttribute('aria-hidden') !== 'true'
  if (visible && 'checkVisibility' in el) {
    visible = (el as Element & { checkVisibility(o?: object): boolean }).checkVisibility({
      checkVisibilityCSS: true,
    })
  }
  cache.set(el, visible)
  return visible
}

/** Walks the visible text nodes under `scope` and records where each character came from. */
export function buildTextMap(scope: Element): TextMap {
  const nodes: Text[] = []
  const nodeOf: number[] = []
  const offsetOf: number[] = []
  let text = ''

  const cache = new Map<Element, boolean>()
  const walker = document.createTreeWalker(scope, NodeFilter.SHOW_TEXT)
  for (let n = walker.nextNode() as Text | null; n; n = walker.nextNode() as Text | null) {
    const parent = n.parentElement
    if (!parent || !isVisible(parent, cache)) continue
    const data = n.data
    if (!data.trim()) continue

    const idx = nodes.push(n) - 1
    // Different text nodes are separate words/blocks: keep them apart.
    if (text && !text.endsWith(' ')) {
      text += ' '
      nodeOf.push(idx)
      offsetOf.push(0)
    }
    let prevSpace = text.endsWith(' ')
    for (let i = 0; i < data.length; i++) {
      const isSpace = /\s/.test(data[i])
      if (isSpace && prevSpace) continue
      text += isSpace ? ' ' : data[i]
      nodeOf.push(idx)
      offsetOf.push(i)
      prevSpace = isSpace
    }
  }

  const trimmedStart = text.length - text.trimStart().length
  return {
    text: text.trim(),
    locate(index) {
      const i = Math.min(Math.max(index + trimmedStart, 0), nodeOf.length - 1)
      return { node: nodes[nodeOf[i]], offset: offsetOf[i] }
    },
  }
}

function makeRange(map: TextMap, start: number, length: number): Range | null {
  if (length <= 0) return null
  try {
    const from = map.locate(start)
    const to = map.locate(start + length - 1)
    const range = document.createRange()
    range.setStart(from.node, from.offset)
    range.setEnd(to.node, Math.min(to.offset + 1, to.node.length))
    return range
  } catch {
    return null
  }
}

export function highlightSentence(map: TextMap, start: number, length: number) {
  if (!highlightSupported) return
  const range = makeRange(map, start, length)
  if (range) CSS.highlights.set(SENTENCE, new Highlight(range))
}

/** Highlights the word being spoken and keeps it comfortably on screen. */
export function highlightWord(map: TextMap, start: number, length: number) {
  if (!highlightSupported) return
  const range = makeRange(map, start, length)
  if (!range) return
  CSS.highlights.set(WORD, new Highlight(range))

  const rect = range.getBoundingClientRect()
  if (rect.top < 90 || rect.bottom > window.innerHeight - 140) {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    window.scrollBy({ top: rect.top - window.innerHeight * 0.35, behavior: reduce ? 'auto' : 'smooth' })
  }
}

export function clearHighlights() {
  if (!highlightSupported) return
  CSS.highlights.delete(SENTENCE)
  CSS.highlights.delete(WORD)
}
