import path from 'path';

import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import vueJsx from '@vitejs/plugin-vue-jsx';
import { VueUseComponentsResolver } from 'unplugin-vue-components/resolvers';
import Components from 'unplugin-vue-components/vite';
import Icons from 'unplugin-icons/vite';
import IconsResolver from 'unplugin-icons/resolver';
import { storybookTest } from '@storybook/addon-vitest/vitest-plugin';
import { playwright } from '@vitest/browser-playwright';

const alias = {
  '@': path.resolve(__dirname, 'resources/js'),
};

/**
 * Mirrors the SFC-handling half of `vite.config.mts`. Inline project configs do NOT inherit
 * root-level `plugins`, so every project must build its own list — and it has to stay in step
 * with the app config, or a module resolves differently under test than it does in a build.
 * The `unit` project previously had no plugins at all, which is why walking a spec's dependency
 * graph (what --changed does) died on the first `.vue` file it reached.
 */
const sfcPlugins = () => [
  vue(),
  vueJsx(),
  Components({
    resolvers: [
      IconsResolver(),
      VueUseComponentsResolver(),
    ],
    // https://github.com/unplugin/unplugin-icons/issues/317#issuecomment-1789146323
    importPathTransform(path) {
      return path === '~icons/fluent/speaker224-regular' ? '~icons/fluent/speaker2-24-regular' : path;
    },
    // Codegen is `vite.config.mts`'s job. Each project instantiates its own plugin chain, so
    // leaving this on has three test projects racing to rewrite the same declaration file.
    dts: false,
  }),
  Icons(),
];

/**
 * Do not set `isolate: false` here. It is tempting — it takes this suite from ~41s to ~9s locally,
 * because a shared jsdom + module registry replaces ~230 rebuilds of both. But 73 of the 227 test
 * files call `vi.mock()` on a module some other test file also mocks (`@inertiajs/vue3` alone in
 * 43 of them), and a shared registry binds each module to whichever factory reached it first.
 * The result is failures that move around with `--maxWorkers`, so CI and a laptop disagree about
 * which tests pass. Unlocking it means converting those files to one shared factory plus
 * per-test `vi.mocked(usePage).mockReturnValue(...)` — the convention 32 files already follow.
 */
const jsdomProject = (name: string, include: string[], exclude?: string[]) => ({
  plugins: sfcPlugins(),
  resolve: { alias },
  // MdGetter.vue dynamic-imports docs/_parts/**/*.md. Tests never render it, but --changed walks
  // every spec's dependency graph and would try to parse the markdown as JS; treating it as an
  // opaque asset is far cheaper here than running the real unplugin-vue-markdown transform.
  assetsInclude: ['**/*.md'],
  test: {
    name,
    environment: 'jsdom',
    setupFiles: ['tests/setup.ts'],
    include,
    ...(exclude ? { exclude } : {}),
  },
});

export default defineConfig({
  resolve: { alias },
  test: {
    globals: true,
    // Beyond the defaults (vitest config, package.json): files every spec depends on implicitly
    // through setupFiles, which the import graph therefore cannot attribute to any one test.
    forceRerunTriggers: [
      '**/package.json/**',
      '**/vitest.config.*/**',
      '**/tests/setup.ts',
      '**/resources/js/mocks/**',
    ],
    // Default projects for daily development (unit + component)
    // Use --project storybook or test:all for browser testing
    projects: [
      // Unit tests project - Services, Composables, Utils
      jsdomProject(
        'unit',
        [
          'resources/js/**/__tests__/**/*.test.ts',
          'resources/js/**/*.test.ts',
        ],
        [
          'resources/js/**/*.component.test.ts',
          'resources/js/**/*.stories.ts',
        ],
      ),
      // Component tests project - Vue components
      jsdomProject('component', ['resources/js/**/__tests__/**/*.component.test.ts']),
      // Storybook tests project - Stories with browser testing
      {
        plugins: [
          ...sfcPlugins(),
          storybookTest(),
        ],
        resolve: {
          alias: {
            ...alias,
            '@/mocks/inertia': path.resolve(__dirname, 'resources/js/mocks/inertia.mock.ts'),
            '@/mocks/i18n': path.resolve(__dirname, 'resources/js/mocks/i18n.ts'),
            '@/mocks/route': path.resolve(__dirname, 'resources/js/mocks/route.ts'),
            // Mock @inertiajs/vue3 to use our mock in Storybook tests
            '@inertiajs/vue3': path.resolve(__dirname, 'resources/js/mocks/inertia.mock.ts'),
          },
        },
        test: {
          name: 'storybook',
          browser: {
            enabled: true,
            provider: playwright(),
            instances: [
              {
                browser: 'chromium',
              },
            ],
            headless: true,
          },
          setupFiles: ['./.storybook/vitest.setup.ts'],
        },
      },
    ],
    // Global coverage configuration
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html', 'lcov'],
      include: [
        'resources/js/Utils/**/*.ts',
        'resources/js/Services/**/*.ts',
        'resources/js/Composables/**/*.ts',
        'resources/js/Components/**/*.vue',
        'resources/js/components/**/*.vue',
      ],
      exclude: [
        '**/*.d.ts',
        '**/*.test.ts',
        '**/*.component.test.ts',
        '**/*.stories.ts',
        'resources/js/Components/NavMain.vue',
      ],
      // No thresholds are enforced. This previously read `thresholds: { global: { lines: 75, … } }`,
      // an Istanbul-style shape that Vitest interprets as a glob named "global" — it matched no
      // file, so the 75% gate never ran on any build. Actual coverage is ~36%. Enabling a real
      // gate is a team decision, not a config fix; the flat shape it needs is:
      //   thresholds: { lines: 36, functions: 33, branches: 29, statements: 36 },
    },
  },
});
