import { router, usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ShowLearningDocuments from '@/Pages/Admin/Learning/ShowLearningDocuments.vue';
import { LearningImportance } from '@/Types/enums';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';
import LearningStepRow from '../LearningStepRow.vue';
import { step } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => {
  vi.clearAllMocks();
  vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/learning/documents' });
});
const data = () => ({
  sections: [
    { id: 'second', title: 'Antras', description: '', order: 2 },
    { id: 'first', title: 'Pirmas', description: 'Mokymų medžiaga', order: 1 },
    { id: 'empty', title: 'Tuščias', description: '', order: 3 },
  ],
  documents: [
    step({ id: 'done', state: 'done', importance: LearningImportance.Essential, section: { id: 'second', title: 'Antras' } }),
    step({ id: 'recommended', section: { id: 'first', title: 'Pirmas' }, glossaryTerms: [{ id: 'quorum', term: 'Kvorumas', abbreviation: '', definition: 'Būtinas narių skaičius.' }] }),
    step({ id: 'unread', state: 'updated', importance: LearningImportance.Essential }),
    step({ id: 'optional', importance: LearningImportance.Optional }),
    step({ id: 'skipped', state: 'skipped', importance: LearningImportance.Essential }),
  ],
});
function render() {
  return mount(ShowLearningDocuments, { props: { documents: data() }, global: { stubs: commonStubs } });
}

describe('Learning documents', () => {
  it('defaults to importance with unread first and resolved counts, reusing glossary rows', () => {
    const wrapper = render();
    const groups = wrapper.findAll('[data-slot="document-section"]');
    expect(groups.map(group => group.get('h2').text())).toEqual([
      'learning.documents.essential 2/3', 'learning.documents.recommended 0/1', 'learning.documents.optional 0/1',
    ]);
    expect(groups[0]!.findAll('[data-step]').map(row => row.attributes('data-step'))).toEqual(['unread', 'done', 'skipped']);
    expect(wrapper.findAllComponents(LearningStepRow).every(row => row.props('showGlossary'))).toBe(true);
    expect(wrapper.text()).toContain('Kvorumas');
    wrapper.unmount();
  });

  it('reads the sections view from the URL, orders sections and puts unsectioned documents last', () => {
    vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/learning/documents?view=sections' });
    const wrapper = render();
    const groups = wrapper.findAll('[data-slot="document-section"]');
    expect(groups.map(group => group.get('h2').text())).toEqual(['Pirmas 0/1', 'Antras 1/1', 'learning.documents.other 1/3']);
    expect(groups[0]!.text()).toContain('Mokymų medžiaga');
    expect(wrapper.text()).not.toContain('Tuščias');
    expect(groups[2]!.findAll('[data-step]')).toHaveLength(3);
    wrapper.unmount();
  });

  it('keeps the selected view in the URL without discarding other query parameters', async () => {
    vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/learning/documents?from=path' });
    const wrapper = render();
    await wrapper.get('[data-testid="documents-view-sections"]').trigger('click');
    expect(router.replace).toHaveBeenLastCalledWith({ url: '/mano/learning/documents?from=path&view=sections', preserveState: true, preserveScroll: true });
    await wrapper.get('[data-testid="documents-view-importance"]').trigger('click');
    expect(router.replace).toHaveBeenLastCalledWith({ url: '/mano/learning/documents?from=path', preserveState: true, preserveScroll: true });
    wrapper.unmount();
  });

  it('hides empty importance groups and teaches the empty state', () => {
    const wrapper = mount(ShowLearningDocuments, { props: { documents: { sections: [], documents: [] } }, global: { stubs: commonStubs } });
    expect(wrapper.findAll('[data-slot="document-section"]')).toHaveLength(0);
    expect(wrapper.get('[data-slot="empty-state"]').text()).toContain('learning.documents.empty_title');
    wrapper.unmount();
  });
});
