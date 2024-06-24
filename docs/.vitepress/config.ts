import { defineConfig } from 'vitepress'
import vueJsx from "@vitejs/plugin-vue-jsx";

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "vusa.lt gidas",
  description: "Gidas kaip naudotis vusa.lt",
  base: '/docs/',
  vite: {
    plugins: [vueJsx()]
  },
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
          { text: 'Informacijos administravimas', link: '/informacijos-administravimas' },
          { text: 'Atstovavimo vykdymas (ViSAK)', link: '/visak' },
          { text: 'Rezervacijų sistema', link: '/rezervaciju-sistema' }
        ]
      },
      {
        text: 'Atsakomybės',
        items: [
          { text: 'Komunikacijai', link: '/atsakomybes/komunikacijai' },
          { text: 'Studentų atstovams (-ėms)', link: '/atsakomybes/studentu-atstovams' },
          { text: 'Administratoriams', link: '/atsakomybes/administratoriams' }
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
