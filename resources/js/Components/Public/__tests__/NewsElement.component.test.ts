import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import NewsElement from '../NewsElement.vue';
import type { News, NewsItem } from '@/Types/contentParts';

/**
 * The news block is a featured article plus a hairline list — no carousel. These cover that
 * split, the optional category chip, and the eyebrow's de-duplication rule. How it *looks* in
 * either theme is Storybook's job; jsdom cannot resolve Tailwind's `dark:` variant.
 */
function makeItem(overrides: Partial<NewsItem> = {}): NewsItem {
  return {
    id: 1,
    title: 'Pirmoji naujiena',
    lang: 'lt',
    short: '<p>Santrauka</p>',
    publish_time: '2026-08-19T16:26:31.000000Z',
    permalink: 'pirmoji-naujiena',
    image: '/uploads/news/one.webp',
    category: null,
    ...overrides,
  };
}

function makeElement(title = ''): News {
  return { json_content: { title } } as unknown as News;
}

const stubs = {
  SmartLink: { props: ['href'], template: '<a :href="href"><slot /></a>' },
};

function mountWith(items: NewsItem[], element: News = makeElement()) {
  return mount(NewsElement, {
    props: { element, prefetchedNews: items },
    global: { stubs },
  });
}

describe('NewsElement', () => {
  it('features the newest article and lists the next three as their own links', () => {
    const items = Array.from({ length: 6 }, (_, i) =>
      makeItem({ id: i + 1, title: `Naujiena ${i + 1}`, permalink: `naujiena-${i + 1}` }));

    const wrapper = mountWith(items);

    expect(wrapper.find('h3').text()).toBe('Naujiena 1');
    // Featured + 3 rows. A fourth row makes the two columns visibly uneven, so the list stops
    // at three even when more articles are available.
    expect(wrapper.findAll('h3')).toHaveLength(4);
    expect(wrapper.text()).toContain('Naujiena 4');
    expect(wrapper.text()).not.toContain('Naujiena 5');
  });

  it('renders a category chip only for articles that have one', () => {
    const wrapper = mountWith([
      makeItem({ id: 1, category: 'Akademinė informacija' }),
      makeItem({ id: 2, title: 'Antra', permalink: 'antra', category: null }),
    ]);

    expect(wrapper.find('[data-slot="tag-chip"]').text()).toBe('Akademinė informacija');
    expect(wrapper.findAll('[data-slot="tag-chip"]')).toHaveLength(1);
  });

  it('drops the eyebrow when the authored title already says "Naujienos"', () => {
    // $t is mocked to return the key verbatim (tests/setup.ts), so the authored title has to
    // match that key for the collision to be reproduced.
    const collides = mountWith([makeItem()], makeElement('Naujienos'));
    expect(collides.find('[data-slot="eyebrow-label"]').exists()).toBe(false);

    const distinct = mountWith([makeItem()], makeElement('Kas naujo bendruomenėje'));
    expect(distinct.find('[data-slot="eyebrow-label"]').exists()).toBe(true);
  });

  it('shows the empty message rather than an empty grid when there is no news', () => {
    const wrapper = mount(NewsElement, {
      props: { element: makeElement(), resolved: { type: 'news', items: [] } },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Nėra naujienų');
    expect(wrapper.findAll('h3')).toHaveLength(0);
  });

  it('treats an empty server payload as an answer, not as missing data', () => {
    // An empty `resolved` used to fall through to the client fetch, so the component showed a
    // skeleton and made a request only to arrive at the same empty state the server already gave.
    const wrapper = mount(NewsElement, {
      props: { element: makeElement(), resolved: { type: 'news', items: [] } },
      global: { stubs },
    });

    expect(wrapper.find('[data-slot="skeleton"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('Nėra naujienų');
  });

  it('is not editable by default — the title renders as plain text', () => {
    const wrapper = mountWith([makeItem()], makeElement('Naujausios'));
    expect(wrapper.find('[contenteditable]').exists()).toBe(false);
    expect(wrapper.find('h2').text()).toBe('Naujausios');
  });

  it('in full-screen editor mode, editing the title bubbles update:element', async () => {
    const wrapper = mount(NewsElement, {
      props: { element: makeElement('Naujausios'), resolved: { type: 'news', items: [makeItem()] }, editable: true, blockKey: 'news-1' },
      global: { stubs },
    });

    const title = wrapper.find('[contenteditable]');
    expect(title.exists()).toBe(true);

    title.element.textContent = 'Kas naujo';
    await title.trigger('input');
    await new Promise(resolve => setTimeout(resolve, 200)); // RCInlineText's debounce

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as News).json_content.title).toBe('Kas naujo');
  });
});
