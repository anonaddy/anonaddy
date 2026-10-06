import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { isDarkTheme } from '../../utils/theme.js'

export const CHART_CYAN = 'rgba(28, 212, 212, 1)'
export const CHART_NAVY = 'rgba(25, 33, 108, 1)'
export const CHART_NAVY_ON_DARK = 'rgba(96, 120, 230, 1)'
export const CHART_PERIWINKLE = 'rgba(123, 147, 219, 1)'

export function useChartTheme() {
  const isDark = computed(() => isDarkTheme(usePage().props.user?.theme ?? 'system'))

  const textColor = computed(() => (isDark.value ? '#e5e7eb' : '#4b5563'))
  const navy = computed(() => (isDark.value ? CHART_NAVY_ON_DARK : CHART_NAVY))

  return { isDark, textColor, navy }
}
