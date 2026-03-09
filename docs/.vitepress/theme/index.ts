// https://vitepress.dev/guide/custom-theme
import { h } from 'vue'
import type { Theme } from 'vitepress'
import DefaultTheme from 'vitepress/theme'
import './style.css'

// Import unified mocks (plain functions, no storybook/test dependency)
import { route } from '@/mocks/route'
import { trans, transChoice } from '@/mocks/i18n'

// Import custom layout and components
import VuSaLayout from './VuSaLayout.vue'
import ComponentDemo from '../../components/ComponentDemo.vue'

export default {
  extends: DefaultTheme,
  Layout: VuSaLayout,
  enhanceApp({ app, router, siteData }) {
    // Register demo wrapper component globally
    app.component('ComponentDemo', ComponentDemo)
    
    // Register global properties to match the main application
    // These are required for component demos to work
    app.config.globalProperties.$t = trans
    app.config.globalProperties.$tChoice = transChoice
    
    // Make route() available globally for component demos
    if (typeof window !== 'undefined') {
      (window as any).route = route
    }
    app.config.globalProperties.route = route
  }
} satisfies Theme
