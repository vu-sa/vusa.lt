import type { StorybookConfig } from '@storybook/vue3-vite';

/**
 * Minimal Storybook Configuration
 * Following official Vue3 + Vite setup guidelines
 */
const config: StorybookConfig = {
  stories: [
    "../resources/js/**/*.stories.@(js|jsx|mjs|ts|tsx)"
  ],
  addons: [
    "@storybook/addon-docs",
    "@storybook/addon-vitest",
    "@storybook/addon-a11y"
  ],
  framework: {
    name: "@storybook/vue3-vite",
    options: {}
  },
  core: {
    disableTelemetry: true
  },
  typescript: {
    check: false
  },
  
  // Minimal Vite configuration for path aliases only
  async viteFinal(config) {
    const { mergeConfig } = await import('vite');
    const path = await import('path');
    
    return mergeConfig(config, {
      resolve: {
        alias: {
          "@": "/resources/js",
          "ziggy-js": "/vendor/tightenco/ziggy/dist",
        }
      }
    });
  }
};

export default config;