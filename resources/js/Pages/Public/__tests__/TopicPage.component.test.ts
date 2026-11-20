import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { defineComponent, h } from 'vue';

import TopicPage from '@/Pages/Public/TopicPage.vue';
import { createBreadcrumbState } from '@/Composables/useBreadcrumbsUnified';
import { createMockPage } from '@/tests/helpers/createMockPage';

function mountPage(description: string | null) {
  // PublicLayout provides the breadcrumb state the page writes into.
  const Host = defineComponent({
    setup() {
      createBreadcrumbState('public');

      return () => h(TopicPage, {
        topic: { id: 1, name: 'Stipendijos', description, alias: 'stipendijos' },
        news: [],
        pages: [],
        events: [],
      });
    },
  });

  return mount(Host, { global: { stubs: { PublicBreadcrumbs: true } } });
}

describe('Public/TopicPage.vue', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage());
  });

  it('renders the tag description as markup, not as escaped tags', () => {
    const lead = mountPage('<p>Apie <strong>stipendijas</strong></p>').find('[data-slot="topic-lead"]');

    expect(lead.find('strong').text()).toBe('stipendijas');
    expect(lead.text()).not.toContain('<p>');
  });

  it('leaves the lead out when the tag has no description', () => {
    expect(mountPage(null).find('[data-slot="topic-lead"]').exists()).toBe(false);
  });
});
