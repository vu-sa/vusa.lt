import { defineConfig, mergeConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import path from 'path'
import { NaiveUiResolver, VueUseComponentsResolver } from 'unplugin-vue-components/resolvers'
import Components from 'unplugin-vue-components/vite'
import Icons from 'unplugin-icons/vite'
import IconsResolver from 'unplugin-icons/resolver'
import { storybookTest } from '@storybook/addon-vitest/vitest-plugin'
import { playwright } from '@vitest/browser-playwright'

export default defineConfig({
  plugins: [
    vue(),
    vueJsx(),
    Components({
      resolvers: [
        IconsResolver(),
        NaiveUiResolver(),
        VueUseComponentsResolver()
      ],
      dts: 'resources/js/Types/components.d.ts',
    }),
    Icons(),
    // Add Storybook test plugin
    storybookTest(),
  ],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, 'resources/js')
    }
  },
  test: {
    globals: true,
    // Default projects for daily development (unit + component)
    // Use --project storybook or test:all for browser testing
    projects: [
      // Unit tests project - Services, Composables, Utils
      {
        test: {
          name: 'unit',
          environment: 'jsdom',
          setupFiles: ['tests/setup.ts'],
          include: [
            'resources/js/**/__tests__/**/*.test.ts',
            'resources/js/**/*.test.ts'
          ],
          exclude: [
            'resources/js/**/*.component.test.ts',
            'resources/js/**/*.stories.ts'
          ]
        }
      },
      // Component tests project - Vue components
      {
        plugins: [
          vue(),
          vueJsx(),
          Components({
            resolvers: [
              IconsResolver(),
              NaiveUiResolver(),
              VueUseComponentsResolver()
            ],
            dts: 'resources/js/Types/components.d.ts',
          }),
          Icons(),
        ],
        resolve: {
          alias: {
            "@": path.resolve(__dirname, 'resources/js')
          }
        },
        test: {
          name: 'component',
          environment: 'jsdom',
          setupFiles: ['tests/setup.ts'],
          include: ['resources/js/**/__tests__/**/*.component.test.ts']
        }
      },
      // Storybook tests project - Stories with browser testing
      {
        plugins: [
          vue(),
          vueJsx(),
          Components({
            resolvers: [
              IconsResolver(),
              NaiveUiResolver(),
              VueUseComponentsResolver()
            ],
            dts: 'resources/js/Types/components.d.ts',
          }),
          Icons(),
          // Add Storybook test plugin for this project too
          storybookTest(),
        ],
        resolve: {
          alias: {
            "@": path.resolve(__dirname, 'resources/js'),
            "@/mocks/inertia": path.resolve(__dirname, 'resources/js/mocks/inertia.mock.ts'),
            "@/mocks/i18n": path.resolve(__dirname, 'resources/js/mocks/i18n.mock.ts'), 
            "@/mocks/route": path.resolve(__dirname, 'resources/js/mocks/route.mock.ts')
          }
        },
        test: {
          name: 'storybook',
          browser: {
            enabled: true,
            provider: playwright(),
            instances: [
              {
                browser: 'chromium'
              }
            ],
            headless: true
          },
          setupFiles: ['./.storybook/vitest.setup.ts']
        }
      }
    ],
    // Global coverage configuration
    coverage: {
      provider: 'istanbul',
      reporter: ['text', 'json', 'html', 'lcov'],
      include: [
        'resources/js/Utils/**/*.ts',
        'resources/js/Services/**/*.ts', 
        'resources/js/Composables/**/*.ts',
        'resources/js/Components/**/*.vue',
        'resources/js/components/**/*.vue'
      ],
      exclude: [
        '**/*.d.ts',
        '**/*.test.ts',
        '**/*.component.test.ts',
        '**/*.stories.ts'
      ],
      thresholds: {
        global: {
          branches: 75,
          functions: 75,
          lines: 75,
          statements: 75
        }
      }
    }
  }
})
