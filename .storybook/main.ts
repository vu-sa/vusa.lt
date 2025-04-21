import type { StorybookConfig } from '@storybook/vue3-vite';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { dirname } from 'node:path';

// Get the directory name using ESM compatible approach
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const config: StorybookConfig = {
  "stories": [
    "../stories/**/*.mdx",
    "../resources/js/**/*.stories.@(js|jsx|mjs|ts|tsx)"
  ],
  "addons": [
    "@storybook/addon-essentials",
    "@chromatic-com/storybook",
    "@storybook/experimental-addon-test",
    "@storybook/addon-coverage",
    "@storybook/addon-a11y"
  ],
  "framework": {
    "name": "@storybook/vue3-vite",
    "options": {}
  },
  // Add Vite configuration for mocking modules
  async viteFinal(config) {
    // Add module aliases for mocks
    if (config.resolve) {
      config.resolve.alias = {
        ...config.resolve.alias,
        '@': path.resolve(__dirname, '../resources/js'),
        '#mocks': path.resolve(__dirname, './mocks'),
      };
    }
    return config;
  }
};

export default config;
