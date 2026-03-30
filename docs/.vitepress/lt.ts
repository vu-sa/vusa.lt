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
      {
        text: 'Pagal vaidmenį',
        items: [
          { text: 'Bendra informacija', link: '/main-functions' },
          { text: 'Studentų atstovams', link: '/roles/student-representatives/faq' },
          { text: 'Administratoriams', link: '/roles/administrators/faq' },
        ]
      },
      { text: 'Naujienos', link: '/blog/' },
      { text: 'Atnaujinimai', link: '/changelog/' },
      { text: 'D.U.K.', link: '/faq' },
    ],

    sidebar: {
      '/roles/student-representatives/': [
        {
          text: 'Studentų atstovams',
          items: [
            { text: 'Atsakomybės', link: '/roles/student-representatives/responsibilities' },
            { text: 'D.U.K.', link: '/roles/student-representatives/faq' },
          ]
        },
        {
          text: 'Taip pat žiūrėkite',
          collapsed: false,
          items: [
            { text: '← Bendra informacija', link: '/main-functions' },
            { text: '← Administratoriams', link: '/roles/administrators/faq' },
          ]
        },
      ],
      '/roles/administrators/': [
        {
          text: 'Administratoriams',
          items: [
            { text: 'D.U.K.', link: '/roles/administrators/faq' },
            { text: 'Rezervacijų sistema', link: '/reservation-system' },
            { text: 'Archyvas', link: '/archive' },
          ]
        },
        {
          text: 'Taip pat žiūrėkite',
          collapsed: false,
          items: [
            { text: '← Bendra informacija', link: '/main-functions' },
            { text: '← Studentų atstovams', link: '/roles/student-representatives/faq' },
          ]
        },
      ],
      '/blog/': [
        {
          text: 'Naujienos',
          items: [
            { text: 'Visos naujienos', link: '/blog/' },
          ]
        },
        {
          text: 'Taip pat žiūrėkite',
          collapsed: false,
          items: [
            { text: '← Atnaujinimai', link: '/changelog/' },
            { text: '← Pradinis', link: '/' },
          ]
        },
      ],
      '/changelog/': [
        {
          text: 'Platformos atnaujinimai',
          items: [
            { text: 'Visi atnaujinimai', link: '/changelog/' },
          ]
        },
        {
          text: 'Taip pat žiūrėkite',
          collapsed: false,
          items: [
            { text: '← Naujienos', link: '/blog/' },
            { text: '← Pradinis', link: '/' },
          ]
        },
      ],
      '/': [
        {
          text: 'Bendra informacija',
          items: [
            { text: 'Pagrindinės funkcijos', link: '/main-functions' },
            { text: 'Dokumentų nuasmeninimas', link: '/dng' },
            { text: 'Informacijos administravimas', link: '/informacijos-administravimas' },
            { text: 'D.U.K.', link: '/faq' },
          ]
        },
        {
          text: 'Kitos platformos',
          collapsed: true,
          items: [
            { text: 'Miro', link: '/kitos-platformos/miro' },
            { text: 'Moodle (Narystės testas)', link: '/kitos-platformos/narystes-testas' },
          ]
        },
        {
          text: 'Pagal vaidmenį',
          collapsed: false,
          items: [
            { text: 'Studentų atstovams →', link: '/roles/student-representatives/faq' },
            { text: 'Administratoriams →', link: '/roles/administrators/faq' },
          ]
        },
      ],
    },
    
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