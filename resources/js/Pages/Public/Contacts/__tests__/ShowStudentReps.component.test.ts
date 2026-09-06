import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import ShowStudentReps from '../ShowStudentReps.vue';

const stubs = {
  StudentRepInstitutionCard: {
    props: ['institution'],
    template: '<div class="mock-rep-card" :data-id="institution.id" :data-name="institution.name">{{ institution.name }}</div>',
  },
  PageTitleBand: {
    props: ['title', 'eyebrow', 'lead'],
    template: '<div class="mock-title-band"><h1><slot /></h1><p>{{ lead }}</p><slot name="breadcrumbs" /><slot name="actions" /></div>',
  },
  PublicBreadcrumbs: {
    template: '<nav class="mock-breadcrumbs" />',
  },
  SmartLink: {
    props: ['href'],
    template: '<a :href="href"><slot /></a>',
  },
  PadalinysSelector: {
    template: '<div class="mock-padalinys-selector" />',
  },
};

describe('ShowStudentReps', () => {
  const mockTypes: App.Entities.Type[] = [
    {
      id: 1,
      title: 'Tarybos',
      slug: 'tarybos',
      institutions: [
        {
          id: 'inst-1',
          name: 'VU SA MIF Taryba',
          duties: [
            {
              id: 'duty-1',
              name: 'Narys',
              current_users: [
                { id: 'user-1', name: 'Vardenis Pavardenis' },
              ],
            },
          ],
        },
        {
          id: 'inst-2',
          name: 'VU SA FF Taryba',
          duties: [
            {
              id: 'duty-2',
              name: 'Narys',
              current_users: [
                { id: 'user-2', name: 'Jonas Jonaitis' },
              ],
            },
          ],
        },
      ],
    } as unknown as App.Entities.Type,
    {
      id: 2,
      title: 'Komisija',
      slug: 'komisija',
      institutions: [
        {
          id: 'inst-3',
          name: 'Etikos komisija',
          duties: [
            {
              id: 'duty-3',
              name: 'Pirmininkas',
              current_users: [
                { id: 'user-3', name: 'Petras Petraitis' },
              ],
            },
          ],
        },
      ],
    } as unknown as App.Entities.Type,
  ];

  it('renders title band, statistics and institutions', () => {
    const wrapper = mount(ShowStudentReps, {
      props: {
        types: mockTypes,
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Studentų atstovai');
    expect(wrapper.text()).toContain('Visi kontaktai');
    expect(wrapper.text()).toContain('3 institucijų');
    expect(wrapper.text()).toContain('3 atstovų');

    // Multi-institution section
    expect(wrapper.text()).toContain('Tarybos');
    // Single-institution section combined
    expect(wrapper.text()).toContain('Kitos institucijos');

    const cards = wrapper.findAll('.mock-rep-card');
    expect(cards).toHaveLength(3);
  });

  it('renders custom category title and toggle button when categoryType is provided', () => {
    const wrapper = mount(ShowStudentReps, {
      props: {
        types: mockTypes,
        categoryType: {
          id: 99,
          slug: 'studentu-atstovu-organas',
          title: 'Studentų atstovų organai',
          description: 'VU studentų valdymo organai',
        },
        showAllTenants: true,
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Studentų atstovų organai');
    expect(wrapper.text()).toContain('Visi padaliniai');
    // Toggle all button is rendered
    expect(wrapper.text()).toContain('Visi');
  });

  it('filters institutions when searching', async () => {
    const wrapper = mount(ShowStudentReps, {
      props: {
        types: mockTypes,
      },
      global: { stubs },
    });

    const input = wrapper.find('input[type="text"]');
    await input.setValue('MIF');

    const cards = wrapper.findAll('.mock-rep-card');
    expect(cards).toHaveLength(1);
    expect(cards[0].attributes('data-name')).toBe('VU SA MIF Taryba');
    expect(wrapper.text()).toContain('1 institucijų');
  });

  it('filters institutions when searching by representative contact name', async () => {
    const wrapper = mount(ShowStudentReps, {
      props: {
        types: mockTypes,
      },
      global: { stubs },
    });

    const input = wrapper.find('input[type="text"]');
    await input.setValue('Petras');

    const cards = wrapper.findAll('.mock-rep-card');
    expect(cards).toHaveLength(1);
    expect(cards[0].attributes('data-name')).toBe('Etikos komisija');
  });

  it('shows empty state when search finds no results', async () => {
    const wrapper = mount(ShowStudentReps, {
      props: {
        types: mockTypes,
      },
      global: { stubs },
    });

    const input = wrapper.find('input[type="text"]');
    await input.setValue('NėraTokio');

    expect(wrapper.findAll('.mock-rep-card')).toHaveLength(0);
    expect(wrapper.text()).toContain('Rezultatų nerasta');
    expect(wrapper.text()).toContain('Išvalyti paiešką');
  });
});
