import { defineConfig, mergeConfig } from 'vitest/config';
import viteConfig from './vite.config.mts';

export default mergeConfig(viteConfig, defineConfig({
  resolve: {
    alias: {
      'vue': 'vue/dist/vue.esm-bundler.js', // For runtime template compilation
      '@': '/resources/js',
    }
  },
  test: {
    name: 'dom',
    environment: 'happy-dom',
    include: ['tests/Unit/**/*.browser.spec.ts'],
    globals: true,
    setupFiles: ['tests/setup.ts'],
  }
}));