import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { beforeEach, expect, it, vi } from 'vitest';

import ShowKnowledgeItem from '@/Pages/Admin/Knowledge/ShowKnowledgeItem.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';
import type { LearningSource } from '@/Types/learning';
import { knowledge } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useRecordTrail');

beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage());
});

const hiddenDocument: LearningSource = {
  type: 'document', url: '/mano/knowledge/items/knowledge-1/open', documentId: 7, documentStatus: null,
  title: 'Studijų reguliaminas', private: true, thumbnailUrl: '/mano/knowledge/items/knowledge-1/thumbnail?v=abc',
};

function render(source: LearningSource | null) {
  return mount(ShowKnowledgeItem, {
    props: { item: knowledge({ source }), inPath: true, coordinators: [], institutionContacts: [], canUpdate: false },
    global: { stubs: {
      ...commonStubs, DiscussionPanel: true,
      Sheet: commonStubs.Dialog, SheetContent: commonStubs.DialogContent,
      SheetHeader: commonStubs.DialogHeader, SheetTitle: commonStubs.DialogTitle,
    } },
  });
}

it('shows a first-page preview that opens a hidden document', () => {
  const wrapper = render(hiddenDocument);
  const preview = wrapper.get('[data-slot="source-preview"]');
  expect(preview.get('img').attributes('src')).toBe(hiddenDocument.thumbnailUrl);
  expect(preview.get('a').attributes('href')).toBe(hiddenDocument.url);
  expect(preview.text()).toContain('Studijų reguliaminas');
  expect(preview.text()).toContain('learning.path.document_platform_only');
  wrapper.unmount();
});

it('falls back to the plain link without a thumbnail or when the image fails', async () => {
  const wrapper = render({ ...hiddenDocument, thumbnailUrl: null });
  expect(wrapper.find('[data-slot="source-preview"]').exists()).toBe(false);
  expect(wrapper.get('[data-slot="step-about"] a').attributes('href')).toBe(hiddenDocument.url);

  await wrapper.setProps({ item: knowledge({ source: hiddenDocument }) });
  await wrapper.get('[data-slot="source-preview"] img').trigger('error');
  expect(wrapper.find('[data-slot="source-preview"]').exists()).toBe(false);
  wrapper.unmount();
});
