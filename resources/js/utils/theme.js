import { ref } from 'vue'

export const prefersDark = ref(window.matchMedia('(prefers-color-scheme: dark)').matches)

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
  prefersDark.value = event.matches
})

export function isDarkTheme(theme) {
  if (theme === 'dark') {
    return true
  }

  if (theme === 'light') {
    return false
  }

  return prefersDark.value
}

export function applyDocumentTheme(theme) {
  document.documentElement.classList.toggle('dark', isDarkTheme(theme))
}
