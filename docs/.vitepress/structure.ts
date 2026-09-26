/**
 * The guide's single ordered outline: the VitePress sidebar and the PDF build
 * (`docs/pdf/build.ts`) both walk it, so the site and the PDF never disagree on order.
 * Workspace chapters mirror `AdminNavigationCatalog` and `lang/admin/lt/shell.php`.
 */

export interface GuidePage {
  text: string
  /** Site path without the `/docs` base; a trailing `/` means `index.md`. */
  link: string
}

export interface GuideChapter {
  text: string
  description?: string
  /** The chapter's own `index.md`, shown before its pages. */
  index?: string
  pages: GuidePage[]
  /** Chapters without a divider page in the PDF (the intro). */
  noPartPage?: boolean
}

export const guide: GuideChapter[] = [
  {
    text: 'Pradžia',
    noPartPage: true,
    pages: [
      { text: 'Įvadas', link: '/ivadas' },
    ],
  },
  {
    text: 'Pagrindai',
    description: 'Kaip sudaryta platforma, kas yra padaliniai, pareigybės ir teisės',
    pages: [
      { text: 'Platforma', link: '/pagrindai/platforma' },
      { text: 'Padaliniai ir pareigybės', link: '/pagrindai/padaliniai-ir-pareigybes' },
      { text: 'Teisės ir rolės', link: '/pagrindai/teises' },
      { text: 'Pranešimai', link: '/pagrindai/pranesimai' },
    ],
  },
  {
    text: 'Mano',
    description: 'Tavo darbai, užduotys ir pranešimai',
    index: '/mano/',
    pages: [
      { text: 'Užduotys', link: '/mano/uzduotys' },
      { text: 'Pranešimai', link: '/mano/pranesimai' },
    ],
  },
  {
    text: 'ViSAK',
    description: 'Posėdžiai, institucijos, darbotvarkės',
    index: '/visak/',
    pages: [
      { text: 'Posėdžiai', link: '/visak/posedziai' },
      { text: 'Darbotvarkės klausimai', link: '/visak/darbotvarkes-klausimai' },
      { text: 'Institucijos', link: '/visak/institucijos' },
      { text: 'Problemos', link: '/visak/problemos' },
      { text: 'Dokumentai', link: '/visak/dokumentai' },
      { text: 'Pareigybių laikotarpiai', link: '/visak/pareigybiu-laikotarpiai' },
      { text: 'Institucijų grafas', link: '/visak/instituciju-grafas' },
      { text: 'Užduočių suvestinė', link: '/visak/uzduociu-suvestine' },
      { text: 'Komentarai', link: '/visak/komentarai' },
    ],
  },
  {
    text: 'Rezervacijos',
    description: 'Įrangos ir daiktų skolinimas',
    index: '/rezervacijos/',
    pages: [
      { text: 'Rezervacijos', link: '/rezervacijos/rezervacijos' },
      { text: 'Ištekliai', link: '/rezervacijos/istekliai' },
      { text: 'Kategorijos', link: '/rezervacijos/kategorijos' },
    ],
  },
  {
    text: 'Svetainė',
    description: 'Puslapiai, naujienos, renginiai',
    index: '/svetaine/',
    pages: [
      { text: 'Puslapiai', link: '/svetaine/puslapiai' },
      { text: 'Naujienos', link: '/svetaine/naujienos' },
      { text: 'Kalendorius', link: '/svetaine/kalendorius' },
      { text: 'Baneriai', link: '/svetaine/baneriai' },
      { text: 'Navigacija', link: '/svetaine/navigacija' },
      { text: 'Greitosios nuorodos', link: '/svetaine/greitosios-nuorodos' },
      { text: 'Renginių tipai', link: '/svetaine/renginiu-tipai' },
      { text: 'Žymos', link: '/svetaine/zymos' },
      { text: 'Failai', link: '/svetaine/failai' },
      { text: 'Studijų rinkiniai', link: '/svetaine/studiju-rinkiniai' },
    ],
  },
  {
    text: 'Organizacija',
    description: 'Nariai, pareigybės, padaliniai',
    index: '/organizacija/',
    pages: [
      { text: 'Nariai', link: '/organizacija/nariai' },
      { text: 'Pareigybės', link: '/organizacija/pareigybes' },
      { text: 'Pareigybių atnaujinimas', link: '/organizacija/pareigybiu-atnaujinimas' },
      { text: 'Padaliniai', link: '/organizacija/padaliniai' },
      { text: 'Studijų programos', link: '/organizacija/studiju-programos' },
      { text: 'Formos ir registracijos', link: '/organizacija/formos-ir-registracijos' },
    ],
  },
  {
    text: 'Sistema',
    description: 'Rolės, leidimai, nustatymai',
    index: '/sistema/',
    pages: [
      { text: 'Rolės ir leidimai', link: '/sistema/roles-ir-leidimai' },
      { text: 'Tipai', link: '/sistema/tipai' },
      { text: 'Ryšiai', link: '/sistema/rysiai' },
      { text: 'Nustatymai', link: '/sistema/nustatymai' },
      { text: 'Sistemos būsena', link: '/sistema/sistemos-busena' },
      { text: 'Laiškų eilė', link: '/sistema/laisku-eile' },
      { text: 'Atstovų rodikliai', link: '/sistema/atstovu-rodikliai' },
      { text: 'Pagalbos užklausos', link: '/sistema/pagalbos-uzklausos' },
      { text: 'Sharepoint failai', link: '/sistema/sharepoint' },
    ],
  },
]

export const pdfFileName = 'vusa-lt-gidas.pdf'

/** `/rezervacijos/` → `rezervacijos/index.md`, `/ivadas` → `ivadas.md`. */
export function sourceFile(link: string): string {
  const path = link.replace(/^\//, '')

  return path === '' || path.endsWith('/') ? `${path}index.md` : `${path}.md`
}
