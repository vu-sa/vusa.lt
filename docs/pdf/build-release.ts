/**
 * Builds the release notes PDF of the newest major version from its changelog page:
 * `node docs/pdf/build-release.ts` → `docs/public/vusa-lt-v3-atnaujinimai.pdf`, shipped by
 * `docs:build` next to the guide PDF. Structure rules: `.ai/rules/changelog.md`.
 */
import fs from 'node:fs'
import path from 'node:path'

import { compileTypst, docsDir, labelPrefix, pdfDir, repoRoot, splitFrontmatter, toCmarkerMarkdown } from './markdown.ts'

const changelogDir = path.join(docsDir, 'changelog')
const major = fs.readdirSync(changelogDir)
  .map(file => file.match(/^v(\d+)\.md$/)?.[1])
  .filter((version): version is string => version !== undefined)
  .sort((a, b) => Number(b) - Number(a))
  .map(version => `v${version}`)[0]

const file = `changelog/${major}.md`
const { body } = splitFrontmatter(fs.readFileSync(path.join(docsDir, file), 'utf-8'))

// Same pattern as the changelog-meta.json build in docs/.vitepress/config.ts.
const newest = body.match(/^## (v[\d.]+) — (.+?) \((\d{4}-\d{2}-\d{2})\)/m)

if (!newest) {
  console.error(`${file}: no "## vX.Y — Title (YYYY-MM-DD)" entry to build release notes from`)
  process.exit(1)
}

// The title page replaces the page's own h1, so each version entry becomes a chapter. Only the
// web page links to the PDF, so the download line is dropped too.
const chapters = body
  .replace(/^# .*\n/m, '')
  .replace(/^.*\]\(\/vusa-lt-[\w-]+\.pdf\).*\n/m, '')
  .replace(/^#(#{1,5}) /gm, '$1 ')

const missingScreenshots: string[] = []
const buildDir = path.join(pdfDir, '.build', 'release')

fs.rmSync(buildDir, { recursive: true, force: true })
fs.mkdirSync(buildDir, { recursive: true })
fs.writeFileSync(path.join(buildDir, `${major}.md`), toCmarkerMarkdown(chapters, {
  prefix: labelPrefix(`/changelog/${major}`),
  file,
  // Nothing but the release notes is in this PDF, so every guide link opens the website.
  pagesInPdf: new Map(),
  missingScreenshots,
}))

if (missingScreenshots.length > 0) {
  console.warn(`Screenshots missing, left out of the release PDF (run npm run docs:screenshots):\n  ${missingScreenshots.join('\n  ')}`)
}

const output = path.join(docsDir, 'public', `vusa-lt-${major}-atnaujinimai.pdf`)

compileTypst(path.join(pdfDir, 'release.typ'), output, {
  source: `/docs/pdf/.build/release/${major}.md`,
  major,
  subtitle: newest[2],
  date: newest[3],
})

console.log(`PDF written to ${path.relative(repoRoot, output)}`)
