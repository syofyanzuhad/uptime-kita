import { ref, onMounted } from 'vue'

const THEME_KEY = 'theme-preference'

export function useTheme() {
  const isDark = ref(false)

  function applyTheme(dark: boolean) {
    isDark.value = dark
    const html = document.documentElement
    if (dark) {
      html.classList.add('dark')
      localStorage.setItem(THEME_KEY, 'dark')
    } else {
      html.classList.remove('dark')
      localStorage.setItem(THEME_KEY, 'light')
    }
  }

  function toggleTheme() {
    applyTheme(!isDark.value)
  }

  function detectSystemTheme() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
  }

  onMounted(() => {
    const saved = localStorage.getItem(THEME_KEY)
    if (saved === 'dark') applyTheme(true)
    else if (saved === 'light') applyTheme(false)
    else applyTheme(detectSystemTheme())
  })

  return { isDark, toggleTheme, applyTheme }
}
