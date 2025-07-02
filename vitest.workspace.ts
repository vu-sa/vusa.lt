import storybookTest from '@storybook/addon-vitest/vitest-plugin';
import path from 'node:path';
import { defineWorkspace } from 'vitest/config';
import { fileURLToPath } from 'node:url';
import Icons from 'unplugin-icons/vite';
import IconsResolver from 'unplugin-icons/resolver';
import Components from 'unplugin-vue-components/vite';
import vuePlugin from '@vitejs/plugin-vue';
const dirname =
  typeof __dirname !== 'undefined' ? __dirname : path.dirname(fileURLToPath(import.meta.url));

export default defineWorkspace([
  {
    // Unit tests (non-browser)
    test: {
      name: 'unit',
      environment: 'jsdom',
      include: ['tests/Unit/**/*.spec.ts'],
      exclude: ['tests/Unit/**/*.browser.spec.ts'],
      globals: true,
      setupFiles: ['tests/setup.ts'],
    },
    plugins: [
      Icons(),
      Components({
        resolvers: [IconsResolver()],
      }),
      vuePlugin({
        script: {
          propsDestructure: true,
        },
      }),
    ],
    resolve: {
      alias: {
        '@': '/resources/js',
        '#mocks': path.resolve(dirname, '.storybook/mocks'),
      }
    },
  },
  {
    // Browser-specific tests
    test: {
      name: 'dom',
      environment: 'jsdom',
      include: ['tests/Unit/**/*.browser.spec.ts'],
      globals: true,
      setupFiles: ['tests/setup.ts'],
    },
    plugins: [
      Icons(),
      Components({
        resolvers: [IconsResolver()],
      }),
    ],
    resolve: {
      alias: {
        '@': '/resources/js',
        'vue': 'vue/dist/vue.esm-bundler.js', // For runtime template compilation
      }
    }
  },
  {
    // This is the path to your existing Vite config file
    extends: './vite.config.mts',
    plugins: [
      storybookTest({
        // The location of your Storybook config, main.js|ts
        configDir: path.join(dirname, '.storybook'),
        // This should match your package.json script to run Storybook
        // The --ci flag will skip prompts and not open a browser
        storybookScript: 'npx storybook dev --ci',
      }),
      Icons(),
      Components({
        resolvers: [IconsResolver()],
      }),
    ],
    test: {
      name: 'storybook',
      // Enable browser mode
      browser: {
        enabled: true,
        name: 'chromium',
        // Make sure to install Playwright
        provider: 'playwright',
        headless: true,
      },
      setupFiles: ['./.storybook/vitest.setup.ts'],
      // Add specific environment variables and mock modules for Storybook
      environmentOptions: {
        jsdom: {
          // Mock globals needed by Storybook
          globals: {
            STORYBOOK_HOOKS_CONTEXT: {}, // Mocked context for Storybook hooks
          }
        }
      },
      deps: {
        // Inline the Storybook Vue package to avoid bundling issues
        // WARNING: Vitest  "deps.inline" is deprecated. If you rely on vite-node directly, use "server.deps.inline" instead. Otherwise, consider using "deps.optimizer.ssr.include"
        // inline: [/@storybook\/vue3/]
      }
    },
    resolve: {
      alias: {
        '@': '/resources/js',
        '#mocks': path.resolve(dirname, '.storybook/mocks'),
        // Add proper aliases for Storybook modules
        '@storybook/vue3': path.resolve(dirname, 'node_modules/@storybook/vue3'),
        // Also map the old imports to the new import format used in Storybook 8
        '@storybook/addon-essentials': path.resolve(dirname, 'node_modules/@storybook/addon-essentials')
      }
    }
  },
]);
