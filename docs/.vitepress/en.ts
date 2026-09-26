import { shared } from './shared'
import { mergeObjects } from './utils'

export default {
  label: 'English',
  lang: 'en',
  link: '/en/',
  title: "vusa.lt guide",
  description: 'VU SR information and internal system guide - all necessary information about the mano.vusa.lt platform',
  themeConfig: mergeObjects(shared, {
    // https://vitepress.dev/reference/default-theme-config
    // The guide itself is Lithuanian-only; English keeps the changelog the admin "What's new" link opens.
    nav: [
      { text: 'Guide (LT)', link: '/ivadas' },
      { text: 'Updates', link: '/en/changelog/v2', activeMatch: '/en/changelog/' },
    ],

    sidebar: [
      {
        text: 'Updates',
        items: [
          { text: 'v2', link: '/en/changelog/v2' },
          { text: 'v1', link: '/en/changelog/v1' },
        ]
      },
    ],
  })
}