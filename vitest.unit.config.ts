import { defineConfig, mergeConfig } from 'vitest/config';
import viteConfig from './vite.config.mts';

export default mergeConfig(viteConfig, defineConfig({
  test: {
    name: 'unit',
    environment: 'node',
    include: ['tests/Unit/**/*.spec.ts'],
    exclude: ['tests/Unit/**/*.browser.spec.ts'],
    globals: true,
    setupFiles: ['tests/setup.ts'],
    deps: {
      inline: ['vue']
    },
  }
}));