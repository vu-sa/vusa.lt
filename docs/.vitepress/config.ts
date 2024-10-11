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
        text: 'Specifinės funkcijos',
        items: [
          { text: 'Atstovavimo vykdymas (ViSAK)', link: '/visak' },
          { text: 'Informacijos administravimas', link: '/informacijos-administravimas' },
          { text: 'Rezervacijų sistema', link: '/rezervaciju-sistema' }
        ]
      },
      {
        text: 'Atsakomybės',
        items: [
          { text: 'Studentų atstovams (-ėms)', link: '/atsakomybes/studentu-atstovams' },
          { text: 'Komunikacijai', link: '/atsakomybes/komunikacijai' },
          { text: 'Administratoriams', link: '/atsakomybes/administratoriams' }
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
