import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import ShowContactCategory from '../ShowContactCategory.vue';

const stubs = {
  NewInstitutionCard: {
    props: ['institution', 'showMetadata'],
    template: '<div class="mock-institution-card" :data-show-metadata="showMetadata">{{ institution.name }}</div>',
  },
  PageTitleBand: {
    props: ['title', 'eyebrow', 'lead'],
    template: '<div class="mock-title-band"><h1>{{ title }}</h1><p>{{ lead }}</p><slot name="breadcrumbs" /><slot name="actions" /></div>',
  },
  PublicBreadcrumbs: {
    template: '<nav class="mock-breadcrumbs" />',
  },
  SmartLink: {
    props: ['href'],
    template: '<a :href="href"><slot /></a>',
  },
};

describe('ShowContactCategory', () => {
  const mockType: App.Entities.Type = {
    id: 1,
    title: 'Padaliniai',
    slug: 'padaliniai',
    description: 'VU SA padaliniai fakultetuose',
    model_type: 'institution',
  } as App.Entities.Type;

  const mockInstitutions: App.Entities.Institution[] = [
    {
      id: 'inst-1',
      name: 'VU SA MIF',
      alias: 'mif',
      tenant: { id: 1, shortname: 'VU SA MIF', alias: 'mif' },
    } as App.Entities.Institution,
    {
      id: 'inst-2',
      name: 'VU SA FF',
      alias: 'ff',
      tenant: { id: 2, shortname: 'VU SA FF', alias: 'ff' },
    } as App.Entities.Institution,
  ];

  it('renders title band and institution cards when institutions exist', () => {
    const wrapper = mount(ShowContactCategory, {
      props: {
        type: mockType,
        institutions: mockInstitutions,
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Padaliniai');
    expect(wrapper.text()).toContain('VU SA padaliniai fakultetuose');
    const cards = wrapper.findAll('.mock-institution-card');
    expect(cards).toHaveLength(2);
    // Suppress tenant tag and type tag metadata chips on category pages
    expect(cards[0].attributes('data-show-metadata')).toBeUndefined();
    expect(wrapper.text()).toContain('2 search.results');
  });

  it('renders empty state when institutions list is empty', () => {
    const wrapper = mount(ShowContactCategory, {
      props: {
        type: mockType,
        institutions: [],
      },
      global: { stubs },
    });

    expect(wrapper.findAll('.mock-institution-card')).toHaveLength(0);
    expect(wrapper.text()).toContain('No contacts available');
    expect(wrapper.text()).toContain('Visi kontaktai');
  });
});
