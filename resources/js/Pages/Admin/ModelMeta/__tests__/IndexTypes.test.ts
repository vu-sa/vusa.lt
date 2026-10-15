import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import IndexTypes from '../IndexTypes.vue';

import type { AdminSection } from '@/Composables/useAdminNavigation';

const dismiss = vi.fn();
vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({ isDismissed: ref(false), dismiss }),
}));

const destination: AdminSection = {
  key: 'instituciju_tipai', label: 'shell.sections.instituciju_tipai',
  routeName: 'institutionTypes.index', routeParams: {}, entityType: 'institution_type',
  description: 'shell.section_descriptions.instituciju_tipai',
  collectionActions: [], matches: ['institutionTypes.*'], startsGroup: false,
};

describe('type directory', () => {
  it('renders the supplied collection links and dismisses discovery on navigation', async () => {
    const wrapper = mount(IndexTypes, {
      props: { destinations: [destination] },
      global: {
        stubs: {
          OverviewPage: { template: '<main><slot /></main>' },
          SpotlightPopover: { template: '<div><slot /></div>' },
        },
      },
    });
    const link = wrapper.get('[data-tile="instituciju_tipai"]');
    expect(link.attributes('href')).toContain('institutionTypes');
    expect(wrapper.findAll('[data-tile]')).toHaveLength(1);
    await link.trigger('click');
    expect(dismiss).toHaveBeenCalledOnce();
  });
});
