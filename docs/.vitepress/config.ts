import { defineConfig } from 'vitepress'
import { fileURLToPath } from 'node:url'
import path from 'node:path'
import tailwindcss from '@tailwindcss/vite'
import Icons from 'unplugin-icons/vite'
import IconsResolver from 'unplugin-icons/resolver'
import Components from 'unplugin-vue-components/vite'
import lt from './lt'
import en from './en'

// Resolve paths for aliases
const __dirname = path.dirname(fileURLToPath(import.meta.url))
const rootDir = path.resolve(__dirname, '../..')

// https://vitepress.dev/reference/site-config
export default defineConfig({
  // Language localization
  locales: {
    root: lt,
    en: en
  },
  
  // Base URL path
  base: '/docs/',
  
  // Enable last updated timestamp based on git
  lastUpdated: true,
  
  // GitHub integration settings
  outDir: '../public/docs',
  cleanUrls: true,

  // Vite configuration for component demos
  vite: {
    plugins: [
      tailwindcss(),
      Components({
        resolvers: [IconsResolver()],
        dts: false,
      }),
      Icons({
        autoInstall: true,
      }),
    ],
    resolve: {
      alias: {
        '@': path.resolve(rootDir, 'resources/js'),
      }
    },
    // Exclude storybook/test from bundle - we use simple function mocks for docs
    optimizeDeps: {
      exclude: ['storybook/test'],
      include: ['reka-ui', 'class-variance-authority', 'clsx', 'tailwind-merge']
    },
    ssr: {
      // Don't externalize app components for SSR
      noExternal: ['@inertiajs/vue3', 'reka-ui', 'class-variance-authority']
    }
  },
  
  // Global search configuration
  themeConfig: {
    search: {
      provider: 'local',
      options: {
        locales: {
          root: {
            translations: {
              button: {
                buttonText: 'Paieška',
                buttonAriaLabel: 'Paieška'
              },
              modal: {
                noResultsText: 'Nėra rezultatų pagal užklausą',
                resetButtonTitle: 'Išvalyti paiešką',
                footer: {
                  selectText: 'pasirinkti',
                  navigateText: 'naršyti',
                  closeText: 'uždaryti',
                }
              }
            }
          },
          en: {
            translations: {
              button: {
                buttonText: 'Search',
                buttonAriaLabel: 'Search'
              },
              modal: {
                noResultsText: 'No results for given search',
                resetButtonTitle: 'Clear search',
                footer: {
                  selectText: 'select',
                  navigateText: 'navigate',
                  closeText: 'close',
                }
              }
            }
          }
        }
      }
    }
  },
  
  // Markdown configuration
  markdown: {
    lineNumbers: true,
    // Enable header anchors for deeper section linking
    headers: {
      level: [0, 3]
    }
  },
  
  // Build optimization
  buildEnd: (siteConfig) => {
    // You can add custom build steps here if needed
  }
})
