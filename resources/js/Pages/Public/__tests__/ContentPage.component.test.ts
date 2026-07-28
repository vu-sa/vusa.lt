import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import ContentPage from '../ContentPage.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

/**
 * Covers the two behaviour changes made to this page: the last-updated line moved out
 * of the per-layout `<header>` into one shared footer at the bottom (always a static
 * date, never relative — see LastUpdatedFooter.vue), and the root padding is
 * `pb-16 md:pb-24` instead of the old `last:pb-2`.
 */
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

  it('renders the root with pb-16 md:pb-24, not the old last:pb-2', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage());

    const wrapper = mount(ContentPage, {
      props: { navigationItemId: 1, page: makePage() },
      global: { stubs },
    });

    const root = wrapper.find('.rc-shell');
    expect(root.exists()).toBe(true);
    expect(root.classes()).toContain('pb-16');
    expect(root.classes()).toContain('md:pb-24');
    expect(root.classes()).not.toContain('last:pb-2');
  });

  it('renders no footer when the page has no last-edited/updated date', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage());

    const wrapper = mount(ContentPage, {
      props: { navigationItemId: 1, page: makePage({ last_edited_at: null, updated_at: null }) },
      global: { stubs },
    });

    expect(wrapper.find('footer').exists()).toBe(false);
  });
});
