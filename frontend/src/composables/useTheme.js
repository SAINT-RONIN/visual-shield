import { ref, computed, watch } from 'vue'

const theme = ref(getInitialTheme())
const isDark = computed(() => theme.value === 'dark')

function getInitialTheme() {
  const stored = localStorage.getItem('theme')
  if (stored === 'light' || stored === 'dark') return stored
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function applyTheme(value) {
  const html = document.documentElement
  if (value === 'dark') {
    html.classList.add('dark')
  } else {
    html.classList.remove('dark')
  }
}

// Apply on module load
applyTheme(theme.value)

watch(theme, (value) => {
  localStorage.setItem('theme', value)
  applyTheme(value)
})

function toggleTheme() {
  theme.value = theme.value === 'dark' ? 'light' : 'dark'
}

export function useTheme() {
  return { theme, isDark, toggleTheme }
}
