// https://vitepress.dev/guide/custom-theme
import type { Theme } from 'vitepress'
import DefaultTheme from 'vitepress/theme'
import './style.css'

import VuSaLayout from './VuSaLayout.vue'

export default {
  extends: DefaultTheme,
  Layout: VuSaLayout,
} satisfies Theme
