import { shared } from './shared.ts'
import { mergeObjects } from './utils.ts'

export default {
  label: 'Updates (EN)',
  lang: 'en',
  link: '/en/',
  title: "vusa.lt guide",
  description: 'VU SR information and internal system guide - all necessary information about the mano.vusa.lt platform',
  themeConfig: mergeObjects(shared, {
    // https://vitepress.dev/reference/default-theme-config
    // The guide itself is Lithuanian-only; English keeps the changelog the admin "What's new" link opens.
    nav: [
      { text: 'Guide (LT)', link: '/ivadas' },
      { text: 'Updates', link: '/en/changelog/v3', activeMatch: '/en/changelog/' },
    ],

    sidebar: [
      {
        text: 'Updates',
        items: [
          { text: 'v3', link: '/en/changelog/v3' },
          { text: 'v2', link: '/en/changelog/v2' },
          { text: 'v1', link: '/en/changelog/v1' },
        ]
      },
    ],
  })
}
