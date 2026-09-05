import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import NewsArticleLayout from '../NewsArticleLayout.vue';

import { commonStubs } from '@/tests/stubs';

/**
 * The article header, which used to be four near-duplicated layout branches and is now one.
 *
 * What is deliberately NOT covered here: whether the masthead's ground actually resolves to the
 * warm tint in light and `--ink` in dark, and whether the near-white headline is legible on it.
 * jsdom has no CSS cascade, so a `.band-masthead` / `dark:` assertion would only be re-reading
 * the class name back. That check lives in Storybook (Public/News) and in the browser test.
 */
const article = {
  id: 7,
  title: 'Pradedama registracija',
  short: '<p>Trumpas įvadas.</p>',
  lang: 'lt',
  publish_time: '2026-08-19T16:26:31.000000Z',
  image: '/images/placeholders/foto1.jpg',
  image_author: 'Vardenė Pavardenė',
  category: { id: 1, name: 'Atstovavimas' },
  reading_time: 4,
  tenant: 'VU SA',
  tags: [],
  highlights: [],
  show_breadcrumbs: true,
} as never;

function render(overrides: Record<string, unknown> = {}) {
  return mount(NewsArticleLayout, {
    props: { article, ...overrides } as never,
    global: {
      stubs: {
        ...commonStubs,
        PublicBreadcrumbs: { template: '<nav class="breadcrumbs" />' },
        RichContentParser: true,
      },
    },
  });
}

describe('NewsArticleLayout', () => {
  it('renders the category chip only when the article has a category', () => {
    expect(render().find('[data-slot="tag-chip"]').text()).toBe('Atstovavimas');

    const withoutCategory = render({ article: { ...article, category: null } });
    expect(withoutCategory.find('[data-slot="tag-chip"]').exists()).toBe(false);
  });

  /**
   * Tags used to be `<button>` + `router.visit`, which cannot be middle-clicked, opened in a new
   * tab, or followed by a crawler. They are links now, and the name resolves from either a plain
   * string or a `{ lt, en }` translation object.
   */
  it('renders tags as links to the tag-filtered archive', () => {
    const wrapper = render({
      article: {
        ...article,
        tags: [
          { id: 1, name: 'Rinkimai', alias: 'rinkimai' },
          { id: 2, name: { lt: 'Studijos', en: 'Studies' }, alias: 'studijos' },
        ],
      },
    });

    const tags = wrapper.findAll('a[data-slot="tag-chip"]');

    expect(tags).toHaveLength(2);
    expect(tags[0]!.text()).toBe('Rinkimai');
    expect(tags[1]!.text()).toBe('Studijos');
    expect(tags[0]!.attributes('href')).toContain('rinkimai');
  });

  it('drops a tag that has no alias, since there is nothing to link it to', () => {
    const wrapper = render({
      article: { ...article, tags: [{ id: 1, name: 'Be nuorodos', alias: null }] },
    });

    expect(wrapper.findAll('a[data-slot="tag-chip"]')).toHaveLength(0);
  });

  it('shows the reading time and the photo credit when the article carries them', () => {
    const wrapper = render();

    expect(wrapper.text()).toContain('news.reading_time');
    expect(wrapper.text()).toContain('Vardenė Pavardenė');

    const bare = render({ article: { ...article, reading_time: null, image_author: null } });
    expect(bare.text()).not.toContain('news.reading_time');
    expect(bare.text()).not.toContain('Vardenė Pavardenė');
  });

  it('offers the other-language link only when there is a counterpart article', () => {
    expect(render().text()).not.toContain('news.read_in_other_language');

    const paired = render({ otherLangURL: '/en/news/registration-opens' });
    expect(paired.text()).toContain('news.read_in_other_language');
  });

  it('renders the reading-size control and the share row', () => {
    const wrapper = render();

    expect(wrapper.find('[data-slot="reading-size-control"]').exists()).toBe(true);
    expect(wrapper.find('[data-slot="share-button"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('news.all_news');
  });

  it('keeps the title band but drops the trail when the author hid breadcrumbs', () => {
    const wrapper = render({ showBreadcrumbs: false });

    expect(wrapper.find('.breadcrumbs').exists()).toBe(false);
    expect(wrapper.find('h1').text()).toBe('Pradedama registracija');
  });

  it('renders the related section only when there are related articles', () => {
    expect(render().text()).not.toContain('news.other_news');

    const related = render({
      relatedArticles: [{
        id: 9,
        title: 'Kita naujiena',
        lang: 'lt',
        short: '<p>…</p>',
        publish_time: '2026-08-01T00:00:00.000000Z',
        permalink: 'kita',
        image: '/images/placeholders/foto2.jpg',
        category: null,
      }],
    });

    expect(related.text()).toContain('news.other_news');
    expect(related.findAll('[data-slot="news-card"]')).toHaveLength(1);
  });
});
