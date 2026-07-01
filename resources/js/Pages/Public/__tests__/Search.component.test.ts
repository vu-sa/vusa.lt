/**
 * Smoke test for the unified public search page.
 *
 * The search behaviour itself (multi_search, distribution, ordering, load-more) is
 * covered by `usePublicMultiSearch.test.ts`. Here we only assert the page wires up:
 * it renders the search input, the content-type toggles, and the initial prompt.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import Search from '../Search.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

describe('Public/Search.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(usePage).mockReturnValue(createMockPage() as any);
  });

  const mountPage = () =>
    mount(Search, {
      props: { initialQuery: '' },
      global: {
        stubs: {
          ...commonStubs,
          Head: true,
          DocumentResults: true,
          MeetingResults: true,
          InstitutionResults: true,
          GenericResults: true,
        },
      },
    });

  it('renders the search input', () => {
    const wrapper = mountPage();
    expect(wrapper.find('input[role="search"]').exists()).toBe(true);
  });

  it('renders a toggle for every content type', () => {
    const wrapper = mountPage();
    const text = wrapper.text();
    // The test i18n bundle returns keys verbatim, so assert on the label keys.
    for (const key of [
      'search.section_institutions',
      'search.section_meetings',
      'search.section_documents',
      'search.section_news',
      'search.section_pages',
      'search.section_calendar',
    ]) {
      expect(text).toContain(key);
    }
  });

  it('shows the initial prompt before any search', () => {
    const wrapper = mountPage();
    expect(wrapper.text()).toContain('search.all_search_prompt');
  });
});
