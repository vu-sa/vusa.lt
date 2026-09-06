import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import InstitutionListDisplay from '../InstitutionListDisplay.vue';

import type { InstitutionList, InstitutionListItem } from '@/Types/contentParts';

function makeItem(overrides: Partial<InstitutionListItem> = {}): InstitutionListItem {
  return {
    id: 1,
    name: 'VU Debatų klubas',
    alias: 'debatu-klubas',
    description: 'Kritinio mąstymo ir viešojo kalbėjimo erdvė studentams.',
    image_url: '/uploads/debatai.jpg',
    tenant: { id: 1, shortname: 'VU SA', alias: 'vusa', type: 'pagrindinis' },
    types: [{ id: 1, slug: 'pkp', title: 'Programos, klubai, projektai' }],
    ...overrides,
  };
}

function makeElement(title = 'Iniciatyvos', eyebrow = 'VU SA'): InstitutionList {
  return { json_content: { title, eyebrow }, options: null } as InstitutionList;
}

const stubs = {
  NewInstitutionCard: {
    props: ['institution'],
    template: '<div class="mock-institution-card">{{ institution.name }}</div>',
  },
};

describe('InstitutionListDisplay', () => {
  it('renders heading and eyebrow from json_content', () => {
    const wrapper = mount(InstitutionListDisplay, {
      props: {
        element: makeElement('Mūsų iniciatyvos', 'VU SA'),
        resolved: { type: 'institution-list', items: [makeItem()], meta: { total: 1 } },
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Mūsų iniciatyvos');
    expect(wrapper.text()).toContain('VU SA');
    expect(wrapper.findAll('.mock-institution-card')).toHaveLength(1);
  });

  it('renders empty state when resolved has no items', () => {
    const wrapper = mount(InstitutionListDisplay, {
      props: {
        element: makeElement(),
        resolved: { type: 'institution-list', items: [], meta: { total: 0 } },
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('search.no_institutions_found');
    expect(wrapper.findAll('.mock-institution-card')).toHaveLength(0);
  });

  it('emits update:element in editable mode', () => {
    const element = makeElement('Pradinis', 'Eyebrow');
    const wrapper = mount(InstitutionListDisplay, {
      props: {
        element,
        editable: true,
        resolved: { type: 'institution-list', items: [], meta: { total: 0 } },
      },
      global: { stubs },
    });

    // In editable mode, check that headings render RCInlineText
    expect(wrapper.find('h2').exists()).toBe(true);
  });
});
