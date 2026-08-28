import { defineConfig } from 'vitepress'
import { fileURLToPath } from 'node:url'
import path from 'node:path'
import fs from 'node:fs'
import lt from './lt'
import en from './en'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

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

  // Fragments shared with the admin UI via MdGetter.vue — included, never standalone pages.
  // `_parts/**` are admin-UI help fragments, not pages; `maintainers/**` is the
  // generated internal coverage dashboard — both are read in-repo, never published.
  srcExclude: ['_parts/**', 'maintainers/**'],

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
  },
  
  // Build optimization - generate changelog metadata for admin UI update indicator
  buildEnd: (siteConfig) => {
    const changelogFile = path.resolve(__dirname, '../changelog/index.md')
    const outDir = path.resolve(__dirname, '../..', 'public/docs')
    
    try {
      // Parse changelog headings: ## vX.Y — Title (YYYY-MM-DD)
      const content = fs.readFileSync(changelogFile, 'utf-8')
      const entryPattern = /^## (v[\d.]+) — .+\((\d{4}-\d{2}-\d{2})\)/gm
      const matches = [...content.matchAll(entryPattern)]
      
      const meta = {
        latestVersion: matches.length > 0 ? matches[0][1] : 'v1.0',
        lastUpdated: matches.length > 0 ? matches[0][2] : new Date().toISOString().substring(0, 10),
        totalEntries: matches.length,
      }
      
      fs.mkdirSync(outDir, { recursive: true })
      fs.writeFileSync(
        path.resolve(outDir, 'changelog-meta.json'),
        JSON.stringify(meta)
      )
    } catch {
      // Silently skip if changelog file doesn't exist yet
    }
  }
})
