/**
 * Builds the PDF edition of the guide from the same markdown and outline as the website:
 * `node docs/pdf/build.ts` → `docs/public/vusa-lt-gidas.pdf`, which the VitePress build then
 * ships at `/docs/vusa-lt-gidas.pdf` (so run it before `docs:build`).
 *
 * VitePress-only syntax is translated for cmarker in `markdown.ts`, not in the pages.
 */
import { execFileSync } from 'node:child_process'
import fs from 'node:fs'
import path from 'node:path'

import { guide, pdfFileName, sourceFile } from '../.vitepress/structure.ts'
import { compileTypst, docsDir, labelPrefix, normaliseLink, pdfDir, repoRoot, splitFrontmatter, toCmarkerMarkdown, typstString } from './markdown.ts'

const buildDir = path.join(pdfDir, '.build')

const pagesInPdf = new Map(guide.flatMap(chapter => [
  ...(chapter.index ? [chapter.index] : []),
  ...chapter.pages.map(page => page.link),
]).map(link => [normaliseLink(link), labelPrefix(link)]))

const missingScreenshots: string[] = []

function renderPage(link: string, h1Level: number): string {
  const file = sourceFile(link)
  const source = fs.readFileSync(path.join(docsDir, file), 'utf-8')
  const { frontmatter, body } = splitFrontmatter(source)
  const prefix = labelPrefix(link)
  const outFile = path.join(buildDir, 'pages', file)

  fs.mkdirSync(path.dirname(outFile), { recursive: true })
  fs.writeFileSync(outFile, toCmarkerMarkdown(body, { prefix, file, pagesInPdf, missingScreenshots }))

  if (frontmatter.tests.length > 0 && !/^## Techninė informacija/m.test(body)) {
    console.warn(`${file}: cites tests but has no "## Techninė informacija" section to hold them`)
  }

  const tests = frontmatter.tests.map(typstString).join(', ')
  const status = { draft: 'Rašoma', partial: 'Dalinis' }[frontmatter.status ?? '']
  const lastUpdated = execFileSync('git', ['log', '-1', '--format=%cs', '--', `docs/${file}`], { cwd: repoRoot, encoding: 'utf8' }).trim()

  return [
    '#pagebreak(weak: true)',
    `#metadata(none) <${prefix}--top>`,
    ...(status ? [`#text(size: 9pt, ${typstString(status)})`] : []),
    `#guide-page(${typstString(`/docs/pdf/.build/pages/${file}`)}, h1-level: ${h1Level})`,
    `#evidence(tests: (${tests}${frontmatter.tests.length === 1 ? ',' : ''}))`,
    ...(frontmatter.reviewed ? [`#text(size: 9pt, ${typstString(`Turinys peržiūrėtas ${frontmatter.reviewed}`)})`] : []),
    ...(lastUpdated ? [`#text(size: 9pt, ${typstString(`Failas pakeistas ${lastUpdated}`)})`] : []),
    '',
  ].join('\n')
}

fs.rmSync(path.join(buildDir, 'pages'), { recursive: true, force: true })
fs.mkdirSync(buildDir, { recursive: true })

const content = [
  '#import "../components/page.typ": guide-page',
  '#import "../components/evidence.typ": evidence',
  '#import "../templates/part-page.typ": part-page',
  '',
]

for (const chapter of guide) {
  if (chapter.noPartPage) {
    content.push('#set heading(numbering: none)')
    chapter.pages.forEach(page => content.push(renderPage(page.link, 2)))
    content.push('#set heading(numbering: (..nums) => if nums.pos().len() <= 2 { numbering("1.1", ..nums) })')
    continue
  }

  content.push(`#part-page(title: ${typstString(chapter.text)}, description: ${chapter.description ? typstString(chapter.description) : 'none'})`)

  if (chapter.index) {
    content.push(renderPage(chapter.index, 2))
  }

  chapter.pages.forEach(page => content.push(renderPage(page.link, 2)))
}

fs.writeFileSync(path.join(buildDir, 'content.typ'), content.join('\n'))

if (missingScreenshots.length > 0) {
  console.warn(`Screenshots missing, left out of the PDF (run npm run docs:screenshots):\n  ${missingScreenshots.join('\n  ')}`)
}

const output = path.join(docsDir, 'public', pdfFileName)

compileTypst(path.join(pdfDir, 'gidas.typ'), output)

console.log(`PDF written to ${path.relative(repoRoot, output)}`)
