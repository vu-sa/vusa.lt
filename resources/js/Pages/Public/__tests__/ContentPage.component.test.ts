import { describe, it, expect, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { createBreadcrumbState } from '@/Composables/useBreadcrumbsUnified';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

import ContentPage from '../ContentPage.vue';

describe('Public/ContentPage.vue', () => {
  const stubs = {
    ...commonStubs,
    RichContentParser: { props: ['content', 'resolved'], template: '<div class="rc-stub" />' },
    TableOfContents: { props: ['links'], template: '<div class="toc-stub" />' },
    HighlightsFloatingButton: { template: '<div class="highlights-stub" />' },
    FeedbackPopover: { template: '<div class="feedback-stub" />' },
  };

  function makePage(overrides: Record<string, unknown> = {}) {
    return {
      title: 'Test Page',
      layout: 'default' as const,
      show_table_of_contents: true,
      show_title: true,
      last_edited_at: '2026-01-15T10:00:00Z',
      updated_at: '2026-01-15T10:00:00Z',
      content: { parts: [] },
      ...overrides,
    };
  }

  it('renders the last-updated footer at the bottom, not in the header', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage());

    const wrapper = mount(ContentPage, {
      props: { navigationItemId: 1, page: makePage() },
      global: { stubs },
    });

    const header = wrapper.find('header');
    expect(header.text()).not.toContain('atnaujinta');
    expect(header.find('time').exists()).toBe(false);

    const footer = wrapper.find('footer');
    expect(footer.exists()).toBe(true);
    expect(footer.find('time').exists()).toBe(true);
    // Static, absolute date — never a relative "prieš X dienų" string, even though
    // the fixture date is recent.
    expect(footer.text()).toContain('2026');
  });

  /**
   * The `default` and `wide` layouts set no font-size of their own, so rich content
   * inherited the 16px browser default across a 44rem/58rem measure — roughly 90
   * characters per line. `focused` already set `text-lg`, and NewsArticleLayout
   * already set a responsive reading size; these two were the outliers.
   *
   * Only the class binding is asserted: jsdom resolves no stylesheet, so the actual
   * rendered font-size (and the resulting characters-per-line) can't be measured here.
   */
  it.each([
    ['default', '.rc-shell .rc-canvas'],
    ['wide', '.rc-canvas'],
  ])('gives the %s layout a responsive reading font-size', (layout, selector) => {
    vi.mocked(usePage).mockReturnValue(createMockPage());

    const wrapper = mount(ContentPage, {
      props: { navigationItemId: 1, page: makePage({ layout }) },
      global: { stubs },
    });

    const canvas = wrapper.find(selector);
    expect(canvas.exists()).toBe(true);
    expect(canvas.classes()).toContain('text-base');
    expect(canvas.classes()).toContain('md:text-[1.0625rem]');
  });

  it('uses a smaller, more open title style for long page titles', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage());

    const wrapper = mount(ContentPage, {
      props: {
        navigationItemId: 1,
        page: makePage({ title: 'A long title that needs more room to remain readable across every viewport' }),
      },
      global: { stubs },
    });

    const title = wrapper.find('h1');
    expect(title.classes()).toContain('text-3xl');
    expect(title.classes()).toContain('sm:text-4xl');
    expect(title.classes()).toContain('lg:text-5xl');
  });

  it('renders no footer when the page has no last-edited/updated date', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage());

    const wrapper = mount(ContentPage, {
      props: { navigationItemId: 1, page: makePage({ last_edited_at: null, updated_at: null }) },
      global: { stubs },
    });

    expect(wrapper.find('footer').exists()).toBe(false);
  });

  it.each([
    [999, [['Tėvinis puslapis', '/lt/tevinis'], ['Vaikinis puslapis', undefined]]],
    [2, [['Navigacijos puslapis', '/lt/navigacija']]],
  ])('uses the appropriate breadcrumb trail for navigation item %s', (navigationItemId, expectedTrail) => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      mainNavigation: [{ id: 2, name: 'Navigacijos puslapis', url: '/lt/navigacija' }],
    }));

    let state!: ReturnType<typeof createBreadcrumbState>;
    const Host = defineComponent({
      setup() {
        state = createBreadcrumbState('public');
        return () =>
          h(ContentPage, {
            navigationItemId,
            page: makePage({
              title: 'Vaikinis puslapis',
              ancestors: [
                { id: 1, title: 'Tėvinis puslapis', permalink: 'tevinis', url: '/lt/tevinis' },
              ],
            }),
          });
      },
    });

    const wrapper = mount(Host, { global: { stubs } });

    expect(state.breadcrumbs.value.slice(1).map(crumb => [crumb.label, crumb.href])).toEqual(expectedTrail);
    wrapper.unmount();
  });
});
