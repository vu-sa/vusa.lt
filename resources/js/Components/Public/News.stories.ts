import type { Meta, StoryObj } from '@storybook/vue3-vite';

import NewsElement from './NewsElement.vue';

import { usePage } from '@/mocks/inertia.storybook';
import type { NewsItem } from '@/Types/contentParts';

/**
 * The `news` block: one featured article and a hairline list beside it.
 *
 * It has no fixture in `Types/samples.ts` because that file deliberately excludes anything that
 * fetches — the block picker must not fire a network request just because someone is browsing
 * it. Here the data is passed straight in through `prefetchedNews`, the same prop the homepage
 * uses, so nothing is fetched here either.
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
  '/images/placeholders/foto4.jpg',
];

const articles: NewsItem[] = [
  {
    id: 1,
    title: '„AD ASTRA“ kviečia atrasti daugiau nei studijas',
    lang: 'lt',
    short: '<p>Rugsėjo 4 d. VU Didžiajame kieme septintąjį kartą vyks integracijos festivalis, kuriame studentai susipažįsta su iniciatyvomis ir viena kita.</p>',
    publish_time: '2026-08-19T16:26:31.000000Z',
    permalink: 'ad-astra',
    image: PLACEHOLDER_IMAGES[0]!,
    category: 'Renginiai',
  },
  {
    id: 2,
    title: 'Gyvenimas VU bendrabutyje: ką svarbu žinoti prieš įsikraustant?',
    lang: 'lt',
    short: '<p>Praktiniai patarimai pirmakursiams.</p>',
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
    short: '<p>Metinis planavimo susitikimas subūrė visų fakultetų atstovus.</p>',
    publish_time: '2026-08-02T15:04:13.000000Z',
    permalink: 'planavimas',
    image: PLACEHOLDER_IMAGES[3]!,
    category: 'Atstovavimas',
  },
];

function renderNews(news: NewsItem[], title = '') {
  return {
    components: { NewsElement },
    setup: () => ({ element: { json_content: { title } }, news }),
    template: '<NewsElement :element="element" :prefetched-news="news" />',
  };
}

const meta: Meta<typeof NewsElement> = {
  title: 'Public/Blocks/News',
  component: NewsElement,
  tags: ['autodocs'],
  decorators: [
    (story) => {
      usePage.mockImplementation(() => ({ props: pageProps }));

      return story();
    },
  ],
};

export default meta;
type Story = StoryObj<typeof NewsElement>;

/** The default: newest article featured, the next three as rows. */
export const Default: Story = { render: () => renderNews(articles) };

/**
 * Articles filed under no category — most of the archive. The chip and the row's category label
 * are omitted rather than replaced by a placeholder.
 */
export const WithoutCategories: Story = {
  render: () => renderNews(articles.map(article => ({ ...article, category: null }))),
};

/**
 * An authored title that differs from the eyebrow. When the author titles the block "Naujienos"
 * — which most do — the eyebrow steps aside instead of stacking the same word twice.
 */
export const WithAuthoredTitle: Story = {
  render: () => renderNews(articles, 'Kas naujo bendruomenėje'),
};

/** One article and nothing to list beside it. */
export const SingleArticle: Story = { render: () => renderNews(articles.slice(0, 1)) };

/** Nothing published yet — a sentence, not an empty grid. */
export const Empty: Story = {
  render: () => ({
    components: { NewsElement },
    setup: () => ({ element: { json_content: { title: '' } } }),
    template: '<NewsElement :element="element" :resolved="{ type: \'news\', items: [] }" />',
  }),
};
