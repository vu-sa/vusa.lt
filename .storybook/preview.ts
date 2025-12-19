import type { Preview } from '@storybook/vue3-vite'
import { setup } from "@storybook/vue3-vite";
import '../resources/css/app.css'

// Minimal setup for Storybook
setup((app) => {
  // Basic global properties - keeping it minimal
  app.config.globalProperties.$t = (key: string) => key
  app.config.globalProperties.$tChoice = (key: string, count: number) => key
  app.config.globalProperties.route = (name: string, params?: any) => `/mock-route/${name}`
});

const preview: Preview = {
  parameters: {
    controls: {
      matchers: {
       color: /(background|color)$/i,
       date: /Date$/i,
      },
    },

    docs: {
      description: {
        component: 'Component documentation'
      }
    },

    a11y: {
      // 'todo' - show a11y violations in the test UI only
      // 'error' - fail CI on a11y violations
      // 'off' - skip a11y checks entirely
      test: 'todo'
    }
  },
};

export default preview;

/* COMMENTED OUT COMPLEX MOCKS - Will re-enable if needed
// Mock laravel-vue-i18n functions
const translateFn = (key: string, params?: any) => {
  // Return a more realistic translation for common keys
  const translations: Record<string, string> = {
    'forms.fields.name': 'Pavadinimas',
    'forms.fields.email': 'El. paštas',
    'forms.fields.type': 'Tipas',
    'Created': 'Sukurta',
    'Updated': 'Atnaujinta',
    'actions.edit': 'Redaguoti',
    'actions.delete': 'Ištrinti',
    'actions.view': 'Peržiūrėti'
  }
  return translations[key] || key
}

const tChoiceFn = (key: string, count: number, params?: any) => {
  const pluralKeys: Record<string, string> = {
    'forms.fields.type': count === 1 ? 'Tipas' : 'Tipai'
  }
  return pluralKeys[key] || key
}

// Mock global window.route function
if (typeof window !== 'undefined') {
  (window as any).route = (name: string, params?: any) => `/mocked-route/${name}`
}

// Setup global properties for Vue components in Storybook
setup((app) => {
  // Register global properties to match those in your application
  app.config.globalProperties.$t = translateFn
  app.config.globalProperties.$tChoice = tChoiceFn
  app.config.globalProperties.route = (name: string, params?: any) => `/mocked-route/${name}`
  app.config.globalProperties.$page = {
    props: {
      app: {
        locale: 'lt',
        name: 'VU SA',
        subdomain: 'www'
      },
      auth: {
        user: {
          id: 1,
          name: 'Test User',
          email: 'test@vusa.lt'
        },
        can: {}
      },
      tenants: [
        { id: 'vusa', name: 'VU SA', shortname: 'VU SA' },
        { id: 'vuif', name: 'VU IF', shortname: 'VU IF' }
      ],
      flash: {},
      otherLangURL: '/en',
    },
  }
  
  // Provide commonly used composables as mocks
  app.provide('searchService', {
    search: () => Promise.resolve({ hits: [], totalHits: 0 }),
    loadInitialFacets: () => Promise.resolve({ facets: {} })
  })
});
*/