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

  // The generated coverage dashboard is read in-repo, never published; pdf/ holds the PDF build's sources.
  srcExclude: ['maintainers/**', 'pdf/**'],

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
    warnAboutMissingScreenshots(siteConfig.srcDir)

    const changelogDir = path.resolve(__dirname, '../changelog')
    const outDir = path.resolve(__dirname, '../..', 'public/docs')

    try {
      // One file per major version (v1.md, v2.md, …); the highest major holds the newest entries.
      const majorFiles = fs.readdirSync(changelogDir)
        .map(file => file.match(/^v(\d+)\.md$/))
        .filter((match): match is RegExpMatchArray => match !== null)
        .sort((a, b) => Number(b[1]) - Number(a[1]))
      const latestChangelog = `v${majorFiles[0][1]}`

      // Parse changelog headings: ## vX.Y — Title (YYYY-MM-DD)
      const entryPattern = /^## (v[\d.]+) — .+\((\d{4}-\d{2}-\d{2})\)/gm
      const matches = [...fs.readFileSync(path.resolve(changelogDir, `${latestChangelog}.md`), 'utf-8').matchAll(entryPattern)]
      const totalEntries = majorFiles.reduce(
        (count, [file]) => count + [...fs.readFileSync(path.resolve(changelogDir, file), 'utf-8').matchAll(entryPattern)].length,
        0,
      )

      const meta = {
        latestVersion: matches.length > 0 ? matches[0][1] : `${latestChangelog}.0`,
        lastUpdated: matches.length > 0 ? matches[0][2] : new Date().toISOString().substring(0, 10),
        latestChangelog,
        totalEntries,
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

// Screenshots are fetched from CI at deploy time, so a renamed or deleted browser test only shows up here.
function warnAboutMissingScreenshots(srcDir: string) {
  const screenshotsDir = path.resolve(srcDir, 'public/screenshots')
  const missing: string[] = []

  for (const file of fs.readdirSync(srcDir, { recursive: true }) as string[]) {
    if (!file.endsWith('.md')) continue

    const locale = file.startsWith(`en${path.sep}`) ? 'en' : 'lt'
    const source = fs.readFileSync(path.resolve(srcDir, file), 'utf-8')

    for (const [, name] of source.matchAll(/<DocScreenshot[^>]*\sname="([\w-]+)"/g)) {
      if (!fs.existsSync(path.resolve(screenshotsDir, locale, `${name}.png`))) {
        missing.push(`${file}: ${locale}/${name}`)
      }
    }
  }

  if (missing.length > 0) {
    console.warn(`Missing docs screenshots (hidden on the page):\n  ${missing.join('\n  ')}`)
  }
}
