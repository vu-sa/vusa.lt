import { shared } from './shared'
import { mergeObjects } from './utils'

export default {
  title: "vusa.lt gidas",
  label: 'Lietuvių',
  lang: 'lt',
  description: 'VU SA informacijos bei vidaus sistemos gidas - visa reikalinga informacija apie mano.vusa.lt platformą',
  themeConfig: mergeObjects(shared, {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: 'Pradinis', link: '/' },
      { text: 'D.U.K.', link: '/faq' }
    ],

    sidebar: [
      { text: 'Pagrindinės funkcijos', link: '/main-functions' },
      { text: 'Dokumentų nuasmeninimas', link: '/dng' },
      {
        text: 'Studentų atstovams',
        items: [
          // { text: 'Funkcijos', link: '/roles/student-representatives/functions' },
          { text: 'Atsakomybės', link: '/roles/student-representatives/responsibilities' },
          { text: 'D.U.K.', link: '/roles/student-representatives/faq' },
        ]
      },
      // {
      //   text: 'Komunikacijos specialistams',
      //   items: [
      //     // { text: 'Funkcijos', link: '/roles/komunikacija/funkcijos' },
      //     // { text: 'Atsakomybės', link: '/roles/komunikacija/atsakomybes' },
      //     // { text: 'D.U.K.', link: '/roles/komunikacija/duk' },
      //   ]
      // },
      {
        text: 'Administratoriams',
        items: [
          // { text: 'Funkcijos', link: '/roles/administratoriai/funkcijos' },
          // { text: 'Atsakomybės', link: '/roles/administratoriai/atsakomybes' },
          { text: 'D.U.K.', link: '/roles/administrators/faq' },
          { text: 'Rezervacijų sistema', link: '/reservation-system' },
          { text: 'Archyvas', link: '/archive' },
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
    
    // Override shared translations for Lithuanian
    editLink: {
      pattern: 'https://github.com/vu-sa/vusa.lt/edit/main/docs/:path',
      text: 'Redaguoti šį puslapį GitHub platformoje'
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