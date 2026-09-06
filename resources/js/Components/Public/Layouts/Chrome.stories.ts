import type { Meta, StoryObj } from '@storybook/vue3-vite';

import MainNavigation from './MainNavigation.vue';

import { usePage } from '@/mocks/inertia.storybook';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import SiteFooter from '@/Components/Public/FullWidth/SiteFooter.vue';
import MainNavigationMenuContent from '@/Components/Public/Nav/MainNavigationMenuContent.vue';
import SecondMenu from '@/Components/Public/Nav/SecondMenu.vue';
import { createBreadcrumbState } from '@/Composables/useBreadcrumbsUnified';

/**
 * The public chrome, migrated to the design system. This exists so the header, secondary bar,
 * mega menu, breadcrumbs and footer can be reviewed together and in both themes — they are the
 * one part of the site every page shares, and the one part no page-level story would cover.
 *
 * `mainNavigation` and `tenant` are shared Inertia props in production (GetNavigationForPublic
 * middleware and PublicController respectively), so they are stubbed here rather than passed in.
 */
/** `NavItem.links` is an array of columns, each an array of links — not a flat list. */
const navItem = (name: string, columns: unknown[][]) => ({ id: name, name, cols: columns.length, links: columns });

const link = (name: string, description?: string, extra: Record<string, unknown> = {}) => ({
  id: name,
  name,
  description,
  link: '#',
  type: 'block-link',
  column: 1,
  ...extra,
});

const pageProps = {
  // `app.path` and `tenants` are what PadalinysSelector/useTenantOptions read; omitting them
  // is what made the first version of this story throw rather than render.
  app: { locale: 'lt', subdomain: 'www', name: 'VU SA', url: 'http://www.vusa.test', path: 'lt' },
  tenants: [
    { id: 1, alias: 'vusa', shortname: 'VU SA', fullname: 'Vilniaus universiteto Studentų atstovybė', type: 'pagrindinis' },
    { id: 2, alias: 'mif', shortname: 'VU SA MIF', fullname: 'VU SA Matematikos ir informatikos fakultete', type: 'padalinys' },
    { id: 3, alias: 'if', shortname: 'VU SA IF', fullname: 'VU SA Istorijos fakultete', type: 'padalinys' },
  ],
  auth: { user: null, can: {} },
  flash: {},
  errors: {},
  otherLangURL: '/en',
  organization: {
    contacts: { it: 'it@vusa.lt', accounting: 'saskaitos@vusa.lt', phone: '+37052687144' },
    social: { facebook: '#', instagram: '#', linkedin: '#' },
    legal: {
      company_code: '193077294',
      vat_code: 'LT100015645710',
      address: { street: 'Universiteto g. 3, Observatorijos kiemelis', city: '01513, Vilnius, Lietuva' },
    },
  },
  tenant: {
    alias: 'vusa',
    shortname: 'VU SA',
    links: [
      { id: 1, text: 'Ad Astra 2026', link: '#' },
      { id: 2, text: 'Tapk VU SA nariu (-e)!', link: '#' },
      { id: 3, text: 'Renginių kalendorius', link: '#' },
    ],
  },
  mainNavigation: [
    navItem('VU SA', [
      [link('Kas yra VU SA?', 'Daugiau nei 30 metų atstovaujame studentams'), link('Struktūra', 'Kaip veikia organizacija')],
      [link('Ad Astra', 'Integracijos festivalis'), link('Pirmakursių stovyklos', 'Bendruomenės pradžia')],
    ]),
    navItem('Studijos ir mokslas', [
      [link('Akademinė informacija', 'Ką verta žinoti apie studijas')],
      [link('Finansinė parama', 'Stipendijos ir parama')],
    ]),
    navItem('Iniciatyvos', [
      [link('START FM', 'Studentų radijas 94.2')],
      [link('Savanorystė', 'Prisidėk prie pokyčių')],
    ]),
    navItem('Kontaktai', [[link('Centrinis biuras', 'Universiteto g. 3')]]),
  ],
  // One column heading left without a `url` on purpose — renders as plain text, not a link.
  footerNavigation: [
    {
      id: 1,
      name: 'Apie mus',
      url: '#',
      links: [
        { id: 11, name: 'Kas yra VU SA?', url: '#', new_tab: false },
        { id: 12, name: 'Struktūra', url: '#', new_tab: false },
      ],
    },
    {
      id: 2,
      name: 'Studentams',
      url: '',
      links: [
        { id: 21, name: 'Akademinė informacija', url: '#', new_tab: false },
        { id: 22, name: 'Finansinė parama', url: '#', new_tab: false },
      ],
    },
    {
      id: 3,
      name: 'Iniciatyvos',
      url: '#',
      links: [
        { id: 31, name: 'START FM', url: 'https://startfm.lt', new_tab: true },
      ],
    },
  ],
};

const meta: Meta = {
  title: 'Public/Chrome',
  tags: ['autodocs'],
  /**
   * Applied as a decorator, not at module scope and not in `beforeEach`.
   *
   * Module scope is global: `usePage` is one shared mock across every story file in a run, so
   * whichever file loaded last wins and these components render against another file's props.
   * `beforeEach` fixes that for the test runner but does not run when Storybook renders a Docs
   * page, which is why the Docs tab threw `path is undefined` from useTenantOptions. A decorator
   * runs on every render path — canvas, docs and test alike.
   */
  decorators: [
    (story) => {
      usePage.mockImplementation(() => ({ props: pageProps }));

      return story();
    },
  ],
};

export default meta;
type Story = StoryObj;

/**
 * The full header. It is `position: fixed` in production, so the story reserves height beneath
 * it rather than letting it overlap the next story on the docs page.
 */
export const Header: Story = {
  render: () => ({
    components: { MainNavigation },
    template: '<div class="relative min-h-64"><MainNavigation :is-theme-dark="false" /></div>',
  }),
};

/** The tenant quick-links row. Sentence case on purpose — the primary nav is the uppercase one. */
export const SecondaryBar: Story = {
  render: () => ({
    components: { SecondMenu },
    template: '<SecondMenu />',
  }),
};

/** One mega-menu panel. The featured card stays fixed-dark in both themes by design. */
export const MegaMenuPanel: Story = {
  render: () => ({
    components: { MainNavigationMenuContent },
    setup: () => ({
      item: navItem('VU SA', [
        [
          { id: 'h1', name: 'Renginiai', type: 'heading' },
          link('Ad Astra', 'Integracijos festivalis'),
          link('Pirmakursių stovyklos', 'Bendruomenės pradžia'),
        ],
        [
          { id: 'h2', name: 'Projektai', type: 'heading' },
          link('START FM', 'Studentų radijas 94.2'),
          link('Savanorystė', 'Prisidėk prie pokyčių'),
        ],
      ]),
    }),
    // `is-used-without-root` swaps the Reka NavigationMenuContent root for a plain div, so the
    // panel can render outside a NavigationMenu — which is exactly what a story needs.
    template: '<div class="max-w-4xl border border-border"><MainNavigationMenuContent :item="item" is-used-without-root /></div>',
  }),
};

/** Hairline trail. Rendered by PublicLayout above every page, so pages never draw their own. */
export const Breadcrumbs: Story = {
  render: () => ({
    components: { PublicBreadcrumbs },
    setup() {
      const state = createBreadcrumbState('public');
      state.set([
        { label: 'Pradinis', href: '/' },
        { label: 'Naujienos', href: '/lt/naujienos' },
        { label: 'Atstovavimas' },
      ]);

      return {};
    },
    template: '<div class="p-4"><PublicBreadcrumbs /></div>',
  }),
};

export const Footer: Story = {
  render: () => ({
    components: { SiteFooter },
    template: '<SiteFooter />',
  }),
};
