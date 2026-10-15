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
    // The user guide is Lithuanian-only; English keeps the changelog the admin Updates link opens
    // and mirrors the developer chapter.
    nav: [
      { text: 'Guide (LT)', link: '/ivadas' },
      { text: 'Updates', link: '/en/changelog/v3', activeMatch: '/en/changelog/' },
      { text: 'Developers', link: '/en/developers/', activeMatch: '/en/developers/' },
    ],

    sidebar: [
      {
        text: 'Developers',
        link: '/en/developers/',
        items: [
          { text: 'Request lifecycle', link: '/en/developers/request-lifecycle' },
          { text: 'Example: a calendar event', link: '/en/developers/example-calendar-event' },
        ]
      },
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
