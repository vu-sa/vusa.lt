import storybookTest from '@storybook/experimental-addon-test/vitest-plugin';
import path from 'node:path';
import { defineWorkspace } from 'vitest/config';
import { fileURLToPath } from 'node:url';
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
    resolve: {
      alias: {
        '@': '/resources/js',
        'vue': 'vue/dist/vue.esm-bundler.js', // For runtime template compilation
      }
    }
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
        storybookScript: 'yarn storybook --ci',
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
    },
  },
]);
