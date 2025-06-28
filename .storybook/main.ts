import type { StorybookConfig } from '@storybook/vue3-vite';
// import { fileURLToPath } from 'node:url';
// import { dirname } from 'node:path';

// Get the directory name using ESM compatible approach
// const __filename = fileURLToPath(import.meta.url);
// const __dirname = dirname(__filename);

const config: StorybookConfig = {
  "stories": [
    "../stories/**/*.mdx",
    "../resources/js/**/*.stories.@(js|jsx|mjs|ts|tsx)"
  ],
  "addons": [
    "@chromatic-com/storybook",
    "@storybook/addon-vitest",
    "@storybook/addon-coverage",
    "@storybook/addon-a11y",
    "@storybook/addon-docs"
  ],
  "framework": {
    "name": "@storybook/vue3-vite",
    "options": {}
  },
  // Add Vite configuration for mocking modules, mostly for preview.ts
  async viteFinal(config) {
    if (config.resolve) {
      config.resolve.alias = {
        ...config.resolve.alias,
        '@': '/resources/js',
        '#mocks': '/.storybook/mocks'
      };
    }
    return config;
  }
};

export default config;
