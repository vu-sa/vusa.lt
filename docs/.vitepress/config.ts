import { defineConfig } from 'vitepress'
import { fileURLToPath } from 'node:url'
import path from 'node:path'
import fs from 'node:fs'
import lt from './lt.ts'
import en from './en.ts'
import { appLinkPath } from './appLinks.ts'
import { changelogEntryPattern, changelogMajors } from './changelogEntries.ts'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

// One file per major (v1.md, v2.md, …); ChangelogNote and the update indicator both need them.
const changelogDir = path.resolve(__dirname, '../changelog')
const changelogFiles: Record<string, string> = Object.fromEntries(
  fs.readdirSync(changelogDir).flatMap((file) => {
    const major = file.match(/^(v\d+)\.md$/)?.[1]

    return major ? [[major, fs.readFileSync(path.join(changelogDir, file), 'utf-8')]] : []
  }),
)
const majors = changelogMajors(changelogFiles)

// https://vitepress.dev/reference/site-config
export default defineConfig({
  // Language localization
  locales: {
    root: lt,
    en: en
  },
  
  // Base URL path
  base: '/gidas/',
  
  // Enable last updated timestamp based on git
  lastUpdated: true,
  
  // GitHub integration settings
  outDir: '../public/gidas',
  cleanUrls: true,

  // The generated coverage dashboard is read in-repo, never published; pdf/ holds the PDF build's sources.
  srcExclude: ['maintainers/**', 'pdf/**'],

  // Global search configuration
  themeConfig: {
    changelogMajors: majors,
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
    container: {
      tipLabel: 'Patarimas',
      infoLabel: 'Informacija',
      warningLabel: 'Dėmesio',
      dangerLabel: 'Svarbu',
      detailsLabel: 'Plačiau',
    },
    config(md) {
      md.renderer.rules.table_open = (_tokens, _idx, _options, env) => {
        const label = env.relativePath?.startsWith('en/') ? 'Scrollable table' : 'Slenkama lentelė'
        return `<div class="doc-table" tabindex="0" role="region" aria-label="${label}"><table>\n`
      }
      md.renderer.rules.table_close = () => '</table></div>\n'

      // `app:/mano/…` links open the platform; the docs base would otherwise turn `/mano/` into the guide's own Mano section.
      const linkOpen = md.renderer.rules.link_open
      md.renderer.rules.link_open = (tokens, idx, options, env, self) => {
        const token = tokens[idx]
        const appPath = appLinkPath(token.attrGet('href') ?? '')

        if (appPath === null) {
          return linkOpen ? linkOpen(tokens, idx, options, env, self) : self.renderToken(tokens, idx, options)
        }

        token.attrSet('href', appPath)
        token.attrSet('target', '_blank')
        token.attrSet('rel', 'noopener')
        token.attrJoin('class', 'app-link')

        return self.renderToken(tokens, idx, options)
      }
    },
  },
  
  // Build optimization - generate changelog metadata for admin UI update indicator
  buildEnd: (siteConfig) => {
    warnAboutMissingScreenshots(siteConfig.srcDir)

    const outDir = path.resolve(__dirname, '../..', 'public/gidas')

    try {
      const latestChangelog = majors[0]!.major
      const latest = changelogFiles[latestChangelog]!.matchAll(changelogEntryPattern).next().value
      const meta = {
        lastUpdated: latest?.[1] ?? majors[0]!.since,
        latestChangelog,
        totalEntries: Object.values(changelogFiles)
          .reduce((count, markdown) => count + [...markdown.matchAll(changelogEntryPattern)].length, 0),
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
