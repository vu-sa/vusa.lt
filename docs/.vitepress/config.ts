import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "vusa.lt gidas",
  locales: {
    root: {
      label: 'Lietuvių',
      lang: 'lt'
    },
    en: {
      label: 'English',
      lang: 'en',
    }
  },
  base: '/docs/',
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: 'Pradinis', link: '/' },
      { text: 'D.U.K.', link: '/duk' }
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
          { text: 'D.U.K.', link: '/duk' }
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
          { text: 'Rezervacijų sistema', link: '/administratoriai' },
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
        text: 'D.U.K.', link: '/duk'
      }
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/vu-sa/vusa.lt' }
    ],
  }
})
