/**
 * Builds the PDF edition of the guide from the same markdown and outline as the website:
 * `node docs/pdf/build.ts` → `docs/public/vusa-lt-gidas.pdf`, which the VitePress build then
 * ships at `/docs/vusa-lt-gidas.pdf` (so run it before `docs:build`).
 *
 * VitePress-only syntax is translated for cmarker here, not in the pages: `:::` containers become
 * `<callout>`, `<DocScreenshot>` becomes `<screenshot>`, and site links become PDF-internal links.
 */
import { execFileSync } from 'node:child_process'
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

import { guide, pdfFileName, sourceFile } from '../.vitepress/structure.ts'

const pdfDir = path.dirname(fileURLToPath(import.meta.url))
const docsDir = path.resolve(pdfDir, '..')
const repoRoot = path.resolve(docsDir, '..')
const buildDir = path.join(pdfDir, '.build')
const siteUrl = 'https://vusa.lt/docs'
const appUrl = 'https://vusa.lt'

const linksInGuide = new Map(guide.flatMap(chapter => [
  ...(chapter.index ? [chapter.index] : []),
  ...chapter.pages.map(page => page.link),
]).map(link => [normaliseLink(link), labelPrefix(link)]))

/** `/rezervacijos/` and `/rezervacijos/index` are the same page. */
function normaliseLink(link: string): string {
  return link.replace(/(\/index)?\/?$/, '') || '/'
}

function labelPrefix(link: string): string {
  return `p-${normaliseLink(link).replace(/^\//, '').replace(/\//g, '-') || 'home'}`
}

/** VitePress' heading slugger (@mdit-vue/shared), so a `#anchor` means the same thing on the site and in the PDF. */
function slugify(text: string): string {
  return text
    .normalize('NFKD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[\u0000-\u001F]/g, '')
    .replace(/[\s~`!@#$%^&*()\-_+=[\]{}|\\;:"'“”‘’„<>,.?/]+/g, '-')
    .replace(/-{2,}/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/^(\d)/, '_$1')
    .toLowerCase()
}

function typstString(value: string): string {
  return `"${value.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"`
}

interface Frontmatter {
  tests: string[]
  reviewed: string | null
}

/** Only the two keys the PDF shows; `docs:coverage` owns the full YAML contract. */
function splitFrontmatter(source: string): { frontmatter: Frontmatter, body: string } {
  const match = source.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n/)
  const yaml = match?.[1] ?? ''
  const testsBlock = yaml.match(/^tests:\s*\n((?:\s+-\s+.+\n?)+)/m)?.[1] ?? ''

  return {
    frontmatter: {
      tests: [...testsBlock.matchAll(/-\s+(\S+)/g)].map(([, test]) => test),
      reviewed: yaml.match(/^last_reviewed:\s*['"]?([\d-]{10})/m)?.[1] ?? null,
    },
    body: match ? source.slice(match[0].length) : source,
  }
}

function rewriteLink(target: string, prefix: string): string {
  if (/^[a-z]+:/i.test(target)) {
    return target
  }

  const [pathPart, anchor] = target.split('#') as [string, string | undefined]

  if (pathPart === '') {
    return `#${prefix}--${anchor}`
  }

  const targetPrefix = linksInGuide.get(normaliseLink(pathPart.replace(/\.md$/, '')))

  if (targetPrefix === undefined) {
    return `${siteUrl}${pathPart}${anchor ? `#${anchor}` : ''}`
  }

  return `#${targetPrefix}--${anchor ?? 'top'}`
}

/** `/mano/...` app addresses become clickable in the PDF; templated ones (`/mano/forms/{id}`) have nowhere to go. */
function linkAppPaths(line: string): string {
  return line.replace(/(?<!\[)`(\/mano(?:\/[\w.-]+)*\/?)`/g, (_, appPath: string) => `[\`${appPath}\`](${appUrl}${appPath})`)
}

function attribute(tag: string, name: string): string | undefined {
  return tag.match(new RegExp(`\\s${name}="([^"]*)"`))?.[1]
}

const missingScreenshots: string[] = []

function toCmarkerMarkdown(body: string, prefix: string, file: string): string {
  const usedSlugs = new Map<string, number>()
  let inFence = false

  const lines = body.split('\n').flatMap((line): string[] => {
    if (/^\s*(```|~~~)/.test(line)) {
      inFence = !inFence

      return [line]
    }

    if (inFence) {
      return [line]
    }

    const container = line.match(/^:::\s*(info|tip|warning|danger|details)\s*(.*)$/)

    if (container) {
      // A title is an attribute string, not markdown, so inline code marks would print literally.
      const title = container[2].trim().replace(/`/g, '')

      return [`<callout type="${container[1]}"${title ? ` title="${title.replace(/"/g, '&quot;')}"` : ''}>`, '']
    }

    if (/^:::\s*$/.test(line)) {
      return ['', '</callout>']
    }

    const heading = line.match(/^(#{1,6})\s+(.*?)\s*(?:\{#([\w-]+)\})?\s*$/)

    if (heading) {
      const text = heading[2]
      let slug = heading[3] ?? slugify(text.replace(/`/g, ''))
      const seen = usedSlugs.get(slug) ?? 0
      usedSlugs.set(slug, seen + 1)
      slug = seen > 0 ? `${slug}-${seen}` : slug

      return [`${heading[1]} ${text}`, '', `<!--raw-typst #metadata(none) <${prefix}--${slug}> -->`]
    }

    if (/^\s*<ChangelogNote\b/.test(line)) {
      const version = attribute(line, 'version') ?? ''
      const href = `${siteUrl}/changelog/${version.split('.')[0]}#${version.replace('.', '-')}`

      return [`<changelog version="${version}" date="${attribute(line, 'date') ?? ''}" title="${attribute(line, 'title') ?? ''}" href="${href}">`]
    }

    if (/^\s*<\/ChangelogNote>/.test(line)) {
      return ['</changelog>']
    }

    if (/^\s*<DocScreenshot\b/.test(line)) {
      const name = attribute(line, 'name')
      const caption = attribute(line, 'caption') ?? attribute(line, 'alt')

      if (!name || !fs.existsSync(path.join(docsDir, 'public/screenshots/lt', `${name}.png`))) {
        missingScreenshots.push(`${file}: ${name}`)

        return []
      }

      return [`<screenshot src="/docs/public/screenshots/lt/${name}.png"${caption ? ` caption="${caption}"` : ''}${/\snarrow\b/.test(line) ? ' narrow="narrow"' : ''}>`]
    }

    return [linkAppPaths(line.replace(/\]\(([^)\s]+)\)/g, (_, target: string) => `](${rewriteLink(target, prefix)})`))]
  })

  return lines.join('\n')
}

function renderPage(link: string, h1Level: number): string {
  const file = sourceFile(link)
  const source = fs.readFileSync(path.join(docsDir, file), 'utf-8')
  const { frontmatter, body } = splitFrontmatter(source)
  const prefix = labelPrefix(link)
  const outFile = path.join(buildDir, 'pages', file)

  fs.mkdirSync(path.dirname(outFile), { recursive: true })
  fs.writeFileSync(outFile, toCmarkerMarkdown(body, prefix, file))

  if (frontmatter.tests.length > 0 && !/^## Techninė informacija/m.test(body)) {
    console.warn(`${file}: cites tests but has no "## Techninė informacija" section to hold them`)
  }

  const tests = frontmatter.tests.map(typstString).join(', ')

  return [
    '#pagebreak(weak: true)',
    `#metadata(none) <${prefix}--top>`,
    `#guide-page(${typstString(`/docs/pdf/.build/pages/${file}`)}, h1-level: ${h1Level})`,
    `#evidence(tests: (${tests}${frontmatter.tests.length === 1 ? ',' : ''}), reviewed: ${frontmatter.reviewed ? typstString(frontmatter.reviewed) : 'none'})`,
    '',
  ].join('\n')
}

fs.rmSync(buildDir, { recursive: true, force: true })
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

try {
  execFileSync('typst', ['compile', '--root', repoRoot, path.join(pdfDir, 'gidas.typ'), output], { stdio: 'inherit' })
} catch (error) {
  if ((error as NodeJS.ErrnoException).code === 'ENOENT') {
    console.error('typst is not installed: https://github.com/typst/typst#installation')
  }

  process.exit(1)
}

console.log(`PDF written to ${path.relative(repoRoot, output)}`)
