import { shared } from './shared'
import { mergeObjects } from './utils'
import { guide, pdfFileName } from './structure'

export default {
  title: "vusa.lt gidas",
  label: 'Lietuvių',
  lang: 'lt',
  description: 'Mano VU SA platformos žinynas administratoriams: kaip veikia kiekviena darbo sritis, puslapis ir teisė',
  themeConfig: mergeObjects(shared, {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: 'Gidas', link: '/ivadas', activeMatch: '^/(?!changelog)' },
      { text: 'Atnaujinimai', link: '/changelog/v2', activeMatch: '/changelog/' },
      // A file, not a page: VitePress adds the `/docs/` base only to page links.
      { text: 'PDF', link: `/docs/${pdfFileName}`, target: '_blank' },
    ],

    sidebar: [
      ...guide.map(chapter => ({
        text: chapter.text,
        link: chapter.index,
        collapsed: false,
        items: chapter.pages,
      })),
      {
        text: 'Atnaujinimai',
        collapsed: true,
        items: [
          { text: 'v2', link: '/changelog/v2' },
          { text: 'v1', link: '/changelog/v1' },
        ]
      },
    ],

    // Override shared translations for Lithuanian
    editLink: {
      pattern: 'https://github.com/vu-sa/vusa.lt/edit/main/docs/:path',
      text: 'Redaguoti šį puslapį GitHub platformoje'
    },
    outline: {
      label: 'Šiame puslapyje'
    },
    lastUpdated: {
      text: 'Paskutinį kartą atnaujinta'
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