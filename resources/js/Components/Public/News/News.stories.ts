import type { Meta, StoryObj } from '@storybook/vue3-vite';

import NewsArticleLayout from './NewsArticleLayout.vue';
import NewsCard from './NewsCard.vue';

import { usePage } from '@/mocks/inertia.storybook';
import type { NewsItem } from '@/Types/contentParts';

/**
 * The news article, end to end.
 *
 * This page is where the masthead is actually checked: jsdom resolves no stylesheet, so the
 * component test can only assert that `.band-masthead` is on the element — whether it reads as a
 * masthead is a question for the Theme toolbar. Switch it and confirm the band goes from the warm
 * tint on paper to the near-black `--ink` slab, and that the brand moves from red to amber with
 * the headline staying legible on both.
 */
const pageProps = {
  app: { locale: 'lt', subdomain: 'www', name: 'VU SA', url: 'http://www.vusa.test', path: 'lt' },
  auth: { user: null, can: {} },
  tenant: { alias: 'vusa', shortname: 'VU SA', subdomain: 'www' },
  flash: {},
  errors: {},
};

const PLACEHOLDER_IMAGES = [
  '/images/placeholders/foto1.jpg',
  '/images/placeholders/foto2.jpg',
  '/images/placeholders/foto3.jpg',
];

const related: NewsItem[] = [
  {
    id: 2,
    title: 'Gyvenimas VU bendrabutyje: ką svarbu žinoti prieš įsikraustant?',
    lang: 'lt',
    short: '<p>Praktiniai patarimai pirmakursiams (-ėms).</p>',
    publish_time: '2026-08-12T07:33:47.000000Z',
    permalink: 'bendrabutis',
    image: PLACEHOLDER_IMAGES[1]!,
    category: 'Gidai',
  },
  {
    id: 3,
    title: 'Pirmakursių stovyklos: pirmasis žingsnis į studentišką gyvenimą',
    lang: 'lt',
    short: '<p>Stovyklos prasideda rugpjūčio pabaigoje.</p>',
    publish_time: '2026-08-08T15:04:05.000000Z',
    permalink: 'stovyklos',
    image: PLACEHOLDER_IMAGES[2]!,
    category: null,
  },
  {
    id: 4,
    title: 'VU SA padaliniai planavo ateinančių metų tikslus',
    lang: 'lt',
    short: '<p>Metinis planavimo susitikimas subūrė visų fakultetų atstovus (-es).</p>',
    publish_time: '2026-08-02T15:04:13.000000Z',
    permalink: 'planavimas',
    image: PLACEHOLDER_IMAGES[0]!,
    category: 'Atstovavimas',
  },
];

const article = {
  id: 1,
  title: 'Pradedama kandidatų (-čių) į VU Senato studentų (-čių) atstovus (-es) registracija',
  short: '<p>VU SA skelbia studentų (-čių) atstovų (-ių) rinkimus į Vilniaus universiteto Senatą ir kviečia VU studentus (-es) kelti savo kandidatūras.</p>',
  lang: 'lt',
  publish_time: '2026-09-01T09:00:00.000000Z',
  image: PLACEHOLDER_IMAGES[0]!,
  image_author: 'Ugniaus Bagdonavičiaus nuotr.',
  category: 'Atstovavimas',
  reading_time: 4,
  tenant: 'VU SA',
  highlights: [
    'Kandidatuoti gali kiekvienas VU studentas (-ė).',
    'Registracija vyksta iki rugsėjo 20 d.',
  ],
  tags: [
    { id: 1, name: 'Rinkimai', alias: 'rinkimai' },
    { id: 2, name: 'Senatas', alias: 'senatas' },
  ],
} as never;

const BODY = `
  <div class="rc-prose">
    <p>Vilniaus universiteto Studentų atstovybė (VU SA) skelbia studentų atstovų rinkimus į
    Vilniaus universiteto Senatą – aukščiausią akademinės savivaldos organą, kuriame priimami
    svarbiausi universiteto sprendimai.</p>
    <h2>Kas gali kandidatuoti?</h2>
    <p>Senatas svarsto ir tvirtina studijų programas, nustato akademinės etikos gaires bei
    sprendžia klausimus, tiesiogiai liečiančius studijų kokybę.</p>
    <blockquote><p>Kiekvienas studentas gali kelti savo kandidatūrą ir tiesiogiai prisidėti prie
    universiteto ateities.</p></blockquote>
  </div>
`;

const meta: Meta<typeof NewsArticleLayout> = {
  title: 'Public/News',
  component: NewsArticleLayout,
  tags: ['autodocs'],
  decorators: [
    (story) => {
      usePage.mockImplementation(() => ({ props: pageProps }));

      return story();
    },
  ],
};

export default meta;
type Story = StoryObj<typeof NewsArticleLayout>;

function renderArticle(overrides: Record<string, unknown> = {}) {
  return {
    components: { NewsArticleLayout },
    setup: () => ({ article, related, body: BODY, overrides }),
    template: `
      <NewsArticleLayout :article="article" :related-articles="related" v-bind="overrides">
        <div v-html="body" />
      </NewsArticleLayout>
    `,
  };
}

/** Everything on: category, tags, photo credit, reading time, highlights, related news. */
export const Article: Story = { render: () => renderArticle() };

/** With a counterpart in the other language, the switch joins the meta row as a hairline chip. */
export const WithLanguageSwitch: Story = {
  render: () => renderArticle({ otherLangURL: '/en/news/senate-elections' }),
};

/**
 * The sparse case, and the one most of the archive actually is: no category, no tags, no photo
 * credit. Nothing should leave a gap where it used to be.
 */
export const Minimal: Story = {
  render: () => ({
    components: { NewsArticleLayout },
    setup: () => ({
      article: { ...(article as Record<string, unknown>), category: null, tags: [], image_author: null, highlights: [] },
      body: BODY,
    }),
    template: `
      <NewsArticleLayout :article="article">
        <div v-html="body" />
      </NewsArticleLayout>
    `,
  }),
};

/**
 * The card at both sizes. `lg` is the one article a band leads with; `sm` is a cell in a grid of
 * many, and its category chip goes quiet — a dozen solid brand blocks in a grid is a dozen
 * accents, and the surface allows one per view.
 */
export const Cards: Story = {
  render: () => ({
    components: { NewsCard },
    setup: () => ({ related }),
    template: `
      <div class="mx-auto max-w-7xl px-5 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-x-8 gap-y-10 lg:grid-cols-2">
          <NewsCard :news="related[0]" size="lg" />
          <div class="grid gap-x-8 gap-y-10 sm:grid-cols-2">
            <NewsCard v-for="item in related" :key="item.id" :news="item" />
          </div>
        </div>
      </div>
    `,
  }),
};
