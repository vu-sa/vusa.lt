import { shared } from './shared.ts'
import { mergeObjects } from './utils.ts'
import { developerGuide, guide, pdfFileName, sourceFile } from './structure.ts'
import fs from 'node:fs'

const sidebarPage = (page: { text: string, link: string }) => {
  const source = fs.readFileSync(new URL(`../${sourceFile(page.link)}`, import.meta.url), 'utf8')
  const status = source.match(/^doc_status: (\w+)/m)?.[1]
  const label = { draft: 'Rašoma', partial: 'Dalinis' }[status ?? '']
  return { ...page, text: label ? `${page.text} · ${label}` : page.text }
}

export default {
  title: "vusa.lt gidas",
  label: 'Lietuvių',
  lang: 'lt',
  description: 'Mano VU SA platformos žinynas administratoriams: kaip veikia kiekviena darbo sritis, puslapis ir teisė',
  themeConfig: mergeObjects(shared, {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: 'Gidas', link: '/darbai', activeMatch: '^/(?!changelog|kurejams)' },
      { text: 'Kūrėjams', link: '/kurejams/', activeMatch: '/kurejams/' },
      { text: 'Atnaujinimai', link: '/changelog/v3', activeMatch: '/changelog/' },
      // A file, not a page: VitePress adds the `/docs/` base only to page links.
      { text: 'PDF', link: `/docs/${pdfFileName}`, target: '_blank' },
    ],

    sidebar: [
      ...guide.map(chapter => ({
        text: chapter.text,
        link: chapter.index,
        collapsed: chapter.text !== 'Pradžia',
        items: chapter.pages.map(sidebarPage),
      })),
      {
        text: developerGuide.text,
        link: developerGuide.index,
        collapsed: true,
        items: developerGuide.pages.map(sidebarPage),
      },
      {
        text: 'Atnaujinimai',
        collapsed: true,
        items: [
          { text: 'v3', link: '/changelog/v3' },
          { text: 'v2', link: '/changelog/v2' },
          { text: 'v1', link: '/changelog/v1' },
        ]
      },
    ],

    // Override shared translations for Lithuanian
    sidebarMenuLabel: 'Turinys',
    skipToContentLabel: 'Pereiti prie turinio',
    returnToTopLabel: 'Į pradžią',
    darkModeSwitchLabel: 'Tema',
    lightModeSwitchTitle: 'Įjungti šviesią temą',
    darkModeSwitchTitle: 'Įjungti tamsią temą',
    editLink: {
      pattern: 'https://github.com/vu-sa/vusa.lt/edit/main/docs/:path',
      text: 'Redaguoti šį puslapį GitHub platformoje'
    },
    outline: {
      label: 'Šiame puslapyje'
    },
    lastUpdated: {
      text: 'Failas pakeistas',
      formatOptions: { year: 'numeric', month: '2-digit', day: '2-digit', forceLocale: true }
    },
    docFooter: {
      prev: 'Ankstesnis puslapis',
      next: 'Kitas puslapis'
    },
    footer: {
      message: 'Išleista pagal MIT licenciją.',
      copyright: `Visos teisės saugomos © ${new Date().getFullYear()} VU Studentų atstovybė`
    }
  })
}
