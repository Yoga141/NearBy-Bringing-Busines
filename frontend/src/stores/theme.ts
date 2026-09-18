import { computed, ref, watch } from 'vue'
import { defineStore } from 'pinia'

/**
 * Light / dark theme.
 *
 * Until the visitor picks one, the site follows the operating system
 * (`prefers-color-scheme`) and keeps following it live - switching the OS to
 * dark at sunset flips the page too. Once they use the toggle, that explicit
 * choice is saved in localStorage and wins on every later visit.
 *
 * The class is applied to <html> before the app even boots by a small inline
 * script in index.html (same storage key, same rules); this store takes over
 * from there. Keep the two in sync if either changes.
 */

export type ThemeChoice = 'light' | 'dark'

/** Also read by the inline script in index.html. */
export const THEME_STORAGE_KEY = 'nearby_theme'

/** Browser UI colour (mobile address bar) per theme: the page background. */
const THEME_COLORS: Record<ThemeChoice, string> = { light: '#faf7f1', dark: '#0f1a26' }

function readChoice(): ThemeChoice | null {
  try {
    const value = localStorage.getItem(THEME_STORAGE_KEY)
    return value === 'light' || value === 'dark' ? value : null
  } catch {
    // Blocked storage (private mode, strict settings): just follow the OS.
    return null
  }
}

function writeChoice(choice: ThemeChoice | null) {
  try {
    if (choice) localStorage.setItem(THEME_STORAGE_KEY, choice)
    else localStorage.removeItem(THEME_STORAGE_KEY)
  } catch {
    // The choice then only lasts for this page view - not worth surfacing.
  }
}

export const useThemeStore = defineStore('theme', () => {
  const media = typeof window !== 'undefined' && window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null

  /** The visitor's explicit choice; null = follow the operating system. */
  const choice = ref<ThemeChoice | null>(readChoice())
  const systemDark = ref(media?.matches ?? false)

  const theme = computed<ThemeChoice>(() => choice.value ?? (systemDark.value ? 'dark' : 'light'))
  const isDark = computed(() => theme.value === 'dark')

  media?.addEventListener('change', (e) => {
    systemDark.value = e.matches
  })

  function apply(value: ThemeChoice) {
    const root = document.documentElement
    root.classList.toggle('dark', value === 'dark')
    // Native controls (scrollbars, date pickers, autofill) follow along.
    root.style.colorScheme = value
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', THEME_COLORS[value])
  }

  watch(theme, apply, { immediate: true })

  function setTheme(value: ThemeChoice) {
    choice.value = value
    writeChoice(value)
  }

  function toggle() {
    setTheme(isDark.value ? 'light' : 'dark')
  }

  /** Forget the explicit choice and go back to following the OS. */
  function followSystem() {
    choice.value = null
    writeChoice(null)
  }

  return { choice, theme, isDark, setTheme, toggle, followSystem }
})
