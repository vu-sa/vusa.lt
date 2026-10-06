// https://vitepress.dev/guide/custom-theme
import type { Theme } from 'vitepress'
import DefaultTheme from 'vitepress/theme'
import './style.css'

import ChangelogNote from './ChangelogNote.vue'
import DocScreenshot from './DocScreenshot.vue'
import VuSaLayout from './VuSaLayout.vue'

export default {
  extends: DefaultTheme,
  Layout: VuSaLayout,
  enhanceApp({ app }) {
    app.component('ChangelogNote', ChangelogNote)
    app.component('DocScreenshot', DocScreenshot)
  },
} satisfies Theme
