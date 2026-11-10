import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import { commonStubs } from '@/tests/stubs';
import { Button } from '@/Components/ui/button';
import LearningDocumentsEntry from '../LearningDocumentsEntry.vue';
import { path, step } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('LearningDocumentsEntry', () => {
  it('shows one recommendation, essential progress and the brand document action', () => {
    const documents = path().documents;
    documents.nextDocument = step({ title: 'Next guide', source: { type: 'url', url: 'https://example.test/read', documentId: null, documentStatus: null, title: null } });
    const wrapper = mount(LearningDocumentsEntry, { props: { documents }, global: { stubs: commonStubs } });
    expect(wrapper.findAll('[role="progressbar"]')).toHaveLength(1);
    expect(wrapper.get('[role="progressbar"]').attributes('aria-valuenow')).toBe('50');
    expect(wrapper.get('[data-slot="next-document"]').text()).toContain('Next guide');
    expect(wrapper.findAll('[data-slot="learning-step-action"]')).toHaveLength(1);
    expect(wrapper.get('a[href="https://example.test/read"]').text()).toBe('learning.path.read_document');
    expect(wrapper.getComponent(Button).props('variant')).toBe('brand');
    expect(wrapper.get('[data-slot="all-documents"]').attributes('href')).toBe(route('learningPath.documents'));
    expect(wrapper.text()).not.toContain('learning.documents.read_total');
    expect(wrapper.html()).not.toContain('spotlight');
    wrapper.unmount();
  });

  it('says every essential document is read, keeps the full link and hides an empty block', async () => {
    const documents = { ...path().documents, nextDocument: null };
    const wrapper = mount(LearningDocumentsEntry, { props: { documents }, global: { stubs: commonStubs } });
    expect(wrapper.get('[data-slot="next-document"]').text()).toContain('learning.documents.all_essential_read');
    expect(wrapper.find('[data-slot="all-documents"]').exists()).toBe(true);
    await wrapper.setProps({ documents: { ...documents, total: 0 } });
    expect(wrapper.find('section').exists()).toBe(false);
    wrapper.unmount();
  });
});
