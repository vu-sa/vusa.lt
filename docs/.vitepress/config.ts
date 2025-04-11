import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  locales: {
    root: {
      title: "vusa.lt gidas",
      label: 'Lietuvių',
      lang: 'lt',
      themeConfig: {
        // https://vitepress.dev/reference/default-theme-config
        nav: [
          { text: 'Pradinis', link: '/' },
          { text: 'D.U.K.', link: '/faq' }
        ],

        sidebar: [
          {
            text: 'Pagrindinės funkcijos', link: '/pagrindines-funkcijos'
          },
          {
            text: 'Studentų atstovavimas (ViSAK)',
            items: [
              { text: 'Funkcijos', link: '/visak' },
              { text: 'Atsakomybės', link: '/atsakomybes/studentu-atstovams' },
              { text: 'D.U.K.', link: '/faq' },
              { text: 'Dokumentų nuasmeninimas', link: '/dng'}
            ]
          },
          {
            text: 'Komunikacija',
            items: [
              { text: 'Informacijos administravimas', link: '/informacijos-administravimas' },
              { text: 'Atsakomybės', link: '/atsakomybes/informacijos-administravimas' },
            ]
          },
          {
            text: 'Administratoriams',
            items: [
              { text: 'Rezervacijų sistema', link: '/reservation-system' },
              { text: 'Atsakomybės', link: '/atsakomybes/administratoriams' },
            ]
          },
          {
            text: 'Kitos platformos',
            items: [
              { text: 'Miro', link: '/kitos-platformos/miro' },
              { text: 'Moodle (Narystės testas)', link: '/kitos-platformos/narystes-testas' }
            ]
          },
          {
            text: 'D.U.K.', link: '/faq'
          }
        ],

        socialLinks: [
          { icon: 'github', link: 'https://github.com/vu-sa/vusa.lt' }
        ],
      }
    },
    en: {
      label: 'English',
      lang: 'en',
      link: '/en/',
      title: "vusa.lt guide",
      themeConfig: {
        // https://vitepress.dev/reference/default-theme-config
        nav: [
          { text: 'Home', link: '/' },
          { text: 'FAQ', link: '/faq' }
        ],

        sidebar: [
          {
            text: 'Main functions', link: '/main-functions'
          },
          {
            text: 'Student representation (ViSAK)',
            items: [
              { text: 'FAQ', link: '/faq' }
            ]
          },
          {
            text: 'For administrators',
            items: [
              { text: 'Reservation system', link: '/reservation-system' },
            ]
          },
          {
            text: 'FAQ', link: '/faq'
          }
        ],

        socialLinks: [
          { icon: 'github', link: 'https://github.com/vu-sa/vusa.lt' }
        ],
      }
    }
  },
  base: '/docs/',
})
