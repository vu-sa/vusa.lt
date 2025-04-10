import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineWorkspace } from 'vitest/config';
import { storybookTest } from '@storybook/experimental-addon-test/vitest-plugin';

const dirname = typeof __dirname !== 'undefined' 
  ? __dirname 
  : path.dirname(fileURLToPath(import.meta.url));

// Define and export the workspace configuration
export default defineWorkspace([
  // Regular unit tests configuration
  {
    extends: './vite.config.mts',
    test: {
      name: 'unit',
      environment: 'node',
      include: ['tests/Unit/**/*.spec.ts'],
      exclude: ['tests/Unit/**/*.browser.spec.ts'],
    },
  },

  // DOM-based tests that need browser environment
  {
    extends: './vite.config.mts',
    test: {
      name: 'dom',
      environment: 'jsdom',
      include: ['tests/Unit/**/*.browser.spec.ts'],
      browser: {
        enabled: true,
        headless: true,
        provider: 'playwright',
        name: 'chromium',
      },
    },
  },

  // Storybook tests configuration
  {
    extends: './vite.config.mts',
    plugins: [
      storybookTest({ configDir: path.join(dirname, '.storybook') }),
    ],
    test: {
      name: 'storybook',
      browser: {
        enabled: true,
        headless: true,
        provider: 'playwright',
        instances: [{ browser: 'chromium' }]
      },
      setupFiles: ['.storybook/vitest.setup.ts'],
    },
  },
]);
