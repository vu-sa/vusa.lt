import { defineConfig } from 'vitepress'
import lt from './lt'
import en from './en'

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
