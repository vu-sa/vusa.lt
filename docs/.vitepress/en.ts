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
      { text: 'FAQ', link: '/en/faq' }
    ],

    sidebar: [
      // {
      //   text: 'Main functions', link: '/en/main-functions'
      // },
      {
        text: 'For Student Representatives',
        items: [
          // { text: 'Functions', link: '/en/roles/student-representatives/functions' },
          // { text: 'Responsibilities', link: '/en/roles/student-representatives/responsibilities' },
          { text: 'FAQ', link: '/en/roles/student-representatives/faq' }
        ]
      },
      // {
      //   text: 'For Communication Specialists',
      //   items: [
      //     { text: 'Functions', link: '/en/roles/communication/functions' },
      //     { text: 'Responsibilities', link: '/en/roles/communication/responsibilities' },
      //     { text: 'FAQ', link: '/en/roles/communication/faq' }
      //   ]
      // },
      {
        text: 'For Administrators',
        items: [
          // { text: 'Functions', link: '/en/roles/administrators/functions' },
          // { text: 'Responsibilities', link: '/en/roles/administrators/responsibilities' },
          { text: 'FAQ', link: '/en/roles/administrators/faq' },
          { text: 'Reservation System', link: '/en/reservation-system' },
          { text: 'Archive', link: '/en/archive' },
        ]
      },
      {
        text: 'FAQ', link: '/en/faq'
      }
    ]
  })
}