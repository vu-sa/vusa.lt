import type { Preview } from '@storybook/vue3-vite'
import { setup } from "@storybook/vue3-vite";
import '../resources/css/app.css'

// Add a mock translation function for Storybook
const translateFn = (key: string, params?: any) => key;

// Setup global properties for Vue components in Storybook
setup((app) => {
  // Register global properties to match those in your application
  app.config.globalProperties.$t = translateFn;
  app.config.globalProperties.$tChoice = (key: string, count: number, params?: any) => key;
  app.config.globalProperties.route = (name: string, params: any) => `/mocked-route/${name}`;
  app.config.globalProperties.$page = {
    props: {
      app: {
        locale: 'lt',
      },
      auth: {
        user: {
          id: 1,
          name: 'Test User',
        },
      },
      tenants: [],
      flash: {},
      otherLangURL: '/en',
    },
  };
});

const preview: Preview = {
  parameters: {
    controls: {
      matchers: {
       color: /(background|color)$/i,
       date: /Date$/i,
      },
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
