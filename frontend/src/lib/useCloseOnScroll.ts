import { onBeforeUnmount, watch, type Ref } from 'vue'

const SCROLL_KEYS = new Set(['PageDown', 'PageUp', 'Home', 'End', ' ', 'ArrowDown', 'ArrowUp'])

/**
 * Closes a floating popup as soon as the visitor scrolls the page, so it never
 * sits over the content they are trying to read.
 *
 * Listens for scroll *intent* (wheel, touch drag, scrolling keys) rather than
 * the `scroll` event itself: the read-along feature and anchor jumps scroll the
 * page programmatically, and that must not dismiss the panel. Gestures that
 * start inside the popup (its own form or list) are ignored, and so are
 * keystrokes typed into a field.
 *
 * While the pointer is over the popup the page behind it is locked too: wheel
 * and touch scrolling there only scrolls the popup's own content (if it has
 * any), never the page underneath.
 */
export function useCloseOnScroll(isOpen: Ref<boolean>, root: Ref<HTMLElement | null>, close: () => void) {
  const inside = (target: EventTarget | null) => target instanceof Node && !!root.value?.contains(target)

  /** True when something between `target` and the popup root can still scroll that way. */
  function canScrollInside(target: EventTarget | null, dy: number): boolean {
    for (let el = target instanceof Element ? target : null; el && root.value?.contains(el); el = el.parentElement) {
      if (el.scrollHeight <= el.clientHeight) continue
      const overflowY = getComputedStyle(el).overflowY
      if (overflowY !== 'auto' && overflowY !== 'scroll') continue
      if (dy < 0 ? el.scrollTop > 0 : el.scrollTop + el.clientHeight < el.scrollHeight - 1) return true
    }
    return false
  }

  const onWheel = (e: WheelEvent) => {
    if (!inside(e.target)) return close()
    // Wheel over the popup: never let it reach the page behind.
    if (!canScrollInside(e.target, e.deltaY)) e.preventDefault()
  }

  let lastY = 0
  const onTouchStart = (e: TouchEvent) => {
    lastY = e.touches[0]?.clientY ?? 0
  }
  const onTouchMove = (e: TouchEvent) => {
    if (!inside(e.target)) return close()
    const y = e.touches[0]?.clientY ?? lastY
    const dy = lastY - y
    lastY = y
    if (!canScrollInside(e.target, dy)) e.cancelable && e.preventDefault()
  }
  const onKey = (e: KeyboardEvent) => {
    if (!SCROLL_KEYS.has(e.key) || inside(e.target)) return
    const t = e.target
    if (t instanceof HTMLElement && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT|BUTTON)$/.test(t.tagName))) return
    close()
  }

  // Not passive: the handlers above call preventDefault to lock the page.
  const opts = { passive: false } as const
  function attach() {
    window.addEventListener('wheel', onWheel, opts)
    window.addEventListener('touchstart', onTouchStart, { passive: true })
    window.addEventListener('touchmove', onTouchMove, opts)
    window.addEventListener('keydown', onKey)
  }
  function detach() {
    window.removeEventListener('wheel', onWheel)
    window.removeEventListener('touchstart', onTouchStart)
    window.removeEventListener('touchmove', onTouchMove)
    window.removeEventListener('keydown', onKey)
  }

  watch(isOpen, (open) => (open ? attach() : detach()), { immediate: true })
  onBeforeUnmount(detach)
}
