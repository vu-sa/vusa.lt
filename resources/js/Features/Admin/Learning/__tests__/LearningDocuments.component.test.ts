import { usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ShowLearningDocuments from '@/Pages/Admin/Learning/ShowLearningDocuments.vue';
import { LearningLayer } from '@/Types/enums';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';
import LearningStepRow from '../LearningStepRow.vue';
import { step } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/learning/documents' }));

describe('Learning documents', () => {
  it('groups documents by layer label in reading order and reuses the progress row with glossary chips', () => {
    const wrapper = mount(ShowLearningDocuments, {
      props: {
        canSeeReps: false,
        documents: [
          step({ id: 'platform', layer: LearningLayer.Platform, layerLabel: 'Platforma' }),
          step({ id: 'tenant', layer: LearningLayer.Tenant, layerLabel: 'VU SA MIF' }),
          step({ id: 'duty', layer: LearningLayer.DutyType, layerLabel: 'Pareigos' }),
          step({ id: 'institution', layer: LearningLayer.InstitutionType, layerLabel: 'Senatas' }),
          step({ id: 'foundation', glossaryTerms: [{ id: 'quorum', term: 'Kvorumas', definition: 'Būtinas narių skaičius.' }] }),
          step({ id: 'foundation-2' }),
        ],
      },
      global: { stubs: commonStubs },
    });
    const sections = wrapper.findAll('[data-slot="document-section"]');
    expect(sections.map(section => section.get('h2').text())).toEqual(['Pagrindas', 'Senatas', 'Pareigos', 'VU SA MIF', 'Platforma']);
    expect(sections[0]?.findAll('[data-step]')).toHaveLength(2);
    expect(wrapper.findAllComponents(LearningStepRow)).toHaveLength(6);
    expect(wrapper.findAllComponents(LearningStepRow).every(row => row.props('showGlossary'))).toBe(true);
    expect(sections[0]?.text()).toContain('Kvorumas');
    expect(wrapper.findAll('[role="checkbox"]')).toHaveLength(6);
    wrapper.unmount();
  });

  it('teaches the empty state', () => {
    const wrapper = mount(ShowLearningDocuments, { props: { documents: [], canSeeReps: false }, global: { stubs: commonStubs } });
    expect(wrapper.get('[data-slot="empty-state"]').text()).toContain('learning.documents.empty_title');
    wrapper.unmount();
  });
});
