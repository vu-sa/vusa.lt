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

function render(source: LearningSource | null, props: Record<string, unknown> = {}) {
  return mount(ShowKnowledgeItem, {
    props: { item: knowledge({ source }), inPath: true, canTrack: true, coordinators: [], institutionContacts: [], canUpdate: false, ...props },
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

it('lets you tick an article that is not on your path, and opens with its summary as prose', async () => {
  const wrapper = render(null, { inPath: false, item: knowledge({ source: null, summary: 'Vienas sakinys.', state: 'pending' }) });
  expect(wrapper.find('[data-testid="step-checkbox"]').exists()).toBe(true);
  expect(wrapper.get('[data-slot="self-learning-state"]').text()).toBe('learning.wiki.self_learning_hint');
  expect(wrapper.get('[data-slot="wiki-prose"] [data-slot="article-summary"]').text()).toBe('Vienas sakinys.');
  wrapper.unmount();

  const readonly = render(null, { inPath: false, canTrack: false });
  expect(readonly.find('[data-testid="step-checkbox"]').exists()).toBe(false);
  readonly.unmount();
});
