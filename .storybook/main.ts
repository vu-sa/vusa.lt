import { fileURLToPath } from 'node:url';

import type { StorybookConfig } from '@storybook/vue3-vite';

/**
 * Absolute path to the project root. Aliases must be real filesystem paths:
 * Vite's dependency optimizer reads aliased bare-import targets (e.g. the
 * `@inertiajs/vue3` mock) off disk, so a root-relative "/resources/js/..."
 * value is resolved against the filesystem root and fails with ENOENT.
 */
const projectRoot = fileURLToPath(new URL('..', import.meta.url));

/**
 * Minimal Storybook Configuration
 * Following official Vue3 + Vite setup guidelines
 */
const config: StorybookConfig = {
  stories: [
    "../resources/js/**/*.stories.@(js|jsx|mjs|ts|tsx)"
  ],
  // NOTE: Don't use storybook-coverage, because of bad dependencies
  addons: [
    "@storybook/addon-a11y",
    "@storybook/addon-docs",
    "@storybook/addon-vitest"
  ],
  // Serve the app's public/ as static root so stories can reference real assets by the same
  // absolute paths production uses (/logos/vusa.lin.hor.svg, /images/...) instead of stubs.
  staticDirs: ["../public"],
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
          "@": path.resolve(projectRoot, 'resources/js'),
          "ziggy-js": path.resolve(projectRoot, 'vendor/tightenco/ziggy/dist'),
          // Mock @inertiajs/vue3 to use our Storybook-safe mock
          // This prevents the 'auth' is undefined error in components using usePage()
          "@inertiajs/vue3": path.resolve(projectRoot, 'resources/js/mocks/inertia.storybook.ts'),
          // Mock laravel-vue-i18n so stories render real copy instead of raw keys.
          // preview.ts installs `$t`/`$tChoice` as globals, but a `<script setup>`
          // binding shadows a global of the same name — and most components do
          // `import { trans as $t } from 'laravel-vue-i18n'`, so without this alias
          // they resolve to the real package (no plugin installed in Storybook) and
          // render the key itself. Long unbreakable keys also distort layout review.
          "laravel-vue-i18n": path.resolve(projectRoot, 'resources/js/mocks/i18n.ts'),
        }
      }
    });
  }
};

export default config;
