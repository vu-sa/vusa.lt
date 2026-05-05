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
    nav: [
      { text: 'Home', link: '/en/' },
      {
        text: 'By Role',
        items: [
          { text: 'General Information', link: '/en/main-functions' },
          { text: 'For Student Representatives', link: '/en/roles/student-representatives/faq' },
          { text: 'For Administrators', link: '/en/roles/administrators/faq' },
        ]
      },
      { text: 'Blog', link: '/en/blog/' },
      { text: 'Updates', link: '/en/changelog/' },
      { text: 'FAQ', link: '/en/faq' },
    ],

    sidebar: {
      '/en/roles/student-representatives/': [
        {
          text: 'For Student Representatives',
          items: [
            { text: 'FAQ', link: '/en/roles/student-representatives/faq' },
          ]
        },
        {
          text: 'See Also',
          collapsed: false,
          items: [
            { text: '← General Information', link: '/en/main-functions' },
            { text: '← For Administrators', link: '/en/roles/administrators/faq' },
          ]
        },
      ],
      '/en/roles/administrators/': [
        {
          text: 'For Administrators',
          items: [
            { text: 'FAQ', link: '/en/roles/administrators/faq' },
            { text: 'Reservation System', link: '/en/reservation-system' },
            { text: 'Archive', link: '/en/archive' },
          ]
        },
        {
          text: 'See Also',
          collapsed: false,
          items: [
            { text: '← General Information', link: '/en/main-functions' },
            { text: '← For Student Representatives', link: '/en/roles/student-representatives/faq' },
          ]
        },
      ],
      '/en/blog/': [
        {
          text: 'Blog',
          items: [
            { text: 'All Posts', link: '/en/blog/' },
          ]
        },
        {
          text: 'See Also',
          collapsed: false,
          items: [
            { text: '← Updates', link: '/en/changelog/' },
            { text: '← Home', link: '/en/' },
          ]
        },
      ],
      '/en/changelog/': [
        {
          text: 'Platform Updates',
          items: [
            { text: 'All Updates', link: '/en/changelog/' },
          ]
        },
        {
          text: 'See Also',
          collapsed: false,
          items: [
            { text: '← Blog', link: '/en/blog/' },
            { text: '← Home', link: '/en/' },
          ]
        },
      ],
      '/en/': [
        {
          text: 'General Information',
          items: [
            { text: 'FAQ', link: '/en/faq' },
          ]
        },
        {
          text: 'By Role',
          collapsed: false,
          items: [
            { text: 'For Student Representatives →', link: '/en/roles/student-representatives/faq' },
            { text: 'For Administrators →', link: '/en/roles/administrators/faq' },
          ]
        },
      ],
    }
  })
}