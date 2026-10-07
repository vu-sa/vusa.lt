/**
 * VitePress markdown → cmarker markdown, shared by the guide (`build.ts`) and the release notes
 * (`build-release.ts`) PDFs. `:::` containers become `<callout>`, `<DocScreenshot>` becomes
 * `<screenshot>`, and site links become PDF-internal labels or absolute site URLs.
 */
import { execFileSync } from 'node:child_process'
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { appLinkPath } from '../.vitepress/appLinks.ts'

export const pdfDir = path.dirname(fileURLToPath(import.meta.url))
export const docsDir = path.resolve(pdfDir, '..')
export const repoRoot = path.resolve(docsDir, '..')
/** The app the PDF was built for (deploy passes APP_URL), so staging PDFs link to staging. */
function resolveAppUrl(): string {
  const fromEnvFile = fs.existsSync(path.join(repoRoot, '.env'))
    ? fs.readFileSync(path.join(repoRoot, '.env'), 'utf-8').match(/^APP_URL=["']?([^"'\s]+)/m)?.[1]
    : undefined

  return (process.env.APP_URL || fromEnvFile || 'https://www.vusa.lt').replace(/\/+$/, '')
}

export const appUrl = resolveAppUrl()
export const siteUrl = `${appUrl}/docs`

/** `/rezervacijos/` and `/rezervacijos/index` are the same page. */
export function normaliseLink(link: string): string {
  return link.replace(/(\/index)?\/?$/, '') || '/'
}

export function labelPrefix(link: string): string {
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

export function typstString(value: string): string {
  return `"${value.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"`
}

export interface Frontmatter {
  tests: string[]
  status: string | null
  reviewed: string | null
}

/** `docs:coverage` owns the full YAML contract. */
export function splitFrontmatter(source: string): { frontmatter: Frontmatter, body: string } {
  const match = source.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n/)
  const yaml = match?.[1] ?? ''
  const testsBlock = yaml.match(/^tests:\s*\n((?:\s+-\s+.+\n?)+)/m)?.[1] ?? ''

  return {
    frontmatter: {
      tests: [...testsBlock.matchAll(/-\s+(\S+)/g)].map(([, test]) => test),
      status: yaml.match(/^doc_status:\s*(draft|partial|reviewed)/m)?.[1] ?? null,
      reviewed: yaml.match(/^last_reviewed:\s*["']?(\d{4}-\d{2}-\d{2})/m)?.[1] ?? null,
    },
    body: match ? source.slice(match[0].length) : source,
  }
}

export interface ConvertOptions {
  /** Label prefix of the page being converted, for its own `#anchor` links. */
  prefix: string
  /** Source path relative to docs/, for warnings. */
  file: string
  /** Pages inside this PDF (normalised link → label prefix); links to any other page go to the site. */
  pagesInPdf: Map<string, string>
  /** Collects `file: name` for frames that are not on disk. */
  missingScreenshots: string[]
}

export function omitTechnicalInformation(body: string): string {
  let omittedLevel: number | null = null
  let fence: string | null = null

  return body.split('\n').filter(line => {
    if (fence !== null) {
      const closing = line.match(/^ {0,3}(`{3,}|~{3,})\s*$/)

      if (closing && closing[1][0] === fence[0] && closing[1].length >= fence.length) {
        fence = null
      }

      return omittedLevel === null
    }

    const opening = line.match(/^ {0,3}(`{3,}|~{3,})/)

    if (opening) {
      fence = opening[1]

      return omittedLevel === null
    }

    const heading = line.match(/^ {0,3}(#{1,6})\s+(.+?)\s*$/)

    if (heading) {
      const level = heading[1].length
      const title = heading[2].replace(/\s+#+$/, '').replace(/\s*\{#[\p{L}\p{N}_-]+\}$/u, '').trim()

      if (omittedLevel !== null && level <= omittedLevel) {
        omittedLevel = null
      }

      if (omittedLevel === null && title === 'Techninė informacija') {
        omittedLevel = level
      }
    }

    return omittedLevel === null
  }).join('\n')
}

function rewriteLink(target: string, { prefix, pagesInPdf }: ConvertOptions): string {
  const appPath = appLinkPath(target)

  if (appPath !== null) {
    return `${appUrl}${appPath}`
  }

  if (/^[a-z]+:/i.test(target)) {
    return target
  }

  const [pathPart, anchor] = target.split('#') as [string, string | undefined]

  if (pathPart === '') {
    return `#${prefix}--${anchor}`
  }

  // Files in docs/public (the PDFs themselves) are served from the site root.
  if (/\.\w+$/.test(pathPart) && !pathPart.endsWith('.md')) {
    return `${siteUrl}${pathPart}`
  }

  const targetPrefix = pagesInPdf.get(normaliseLink(pathPart.replace(/\.md$/, '')))

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

export function toCmarkerMarkdown(body: string, options: ConvertOptions): string {
  const { prefix, file, missingScreenshots } = options
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

    const heading = line.match(/^(#{1,6})\s+(.*?)\s*(?:\{#([\p{L}\p{N}_-]+)\})?\s*$/u)

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

      return [`<screenshot src="/docs/public/screenshots/lt/${name}.png"${caption ? ` caption="${caption}"` : ''}${/\snarrow\b/.test(line) ? ' narrow="narrow"' : ''}${/\sphone\b/.test(line) ? ' phone="phone"' : ''}>`]
    }

    const marked = line.replace(/\[([^\]]+)\]\((app:[^)\s]+)\)/g, '[$1 ↗]($2)')

    return [linkAppPaths(marked.replace(/\]\(([^)\s]+)\)/g, (_, target: string) => `](${rewriteLink(target, options)})`))]
  })

  return lines.join('\n')
}

/** Compile a Typst entry file; a missing `typst` binary gets an install hint instead of a stack trace. */
export function compileTypst(entry: string, output: string, inputs: Record<string, string> = {}): void {
  const inputArgs = Object.entries(inputs).flatMap(([key, value]) => ['--input', `${key}=${value}`])

  try {
    execFileSync('typst', ['compile', '--root', repoRoot, ...inputArgs, entry, output], { stdio: 'inherit' })
  } catch (error) {
    if ((error as NodeJS.ErrnoException).code === 'ENOENT') {
      console.error('typst is not installed: https://github.com/typst/typst#installation')
    }

    process.exit(1)
  }
}
