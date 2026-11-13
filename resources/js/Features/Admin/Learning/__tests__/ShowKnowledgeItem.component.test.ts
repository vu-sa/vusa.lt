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

it.each([true, false])('keeps the eyebrow in the title grid when tracking is %s', (canTrack) => {
  const wrapper = render(null, { canTrack });
  const heading = wrapper.get('[data-slot="step-heading"]');
  const eyebrow = heading.get('[data-slot="step-eyebrow"]');

  expect(eyebrow.text()).toBe('learning.kinds.term');
  expect(eyebrow.classes('col-start-2')).toBe(canTrack);
  expect(heading.classes('grid-cols-[2.75rem_minmax(0,1fr)]')).toBe(canTrack);
  expect(heading.find('[data-testid="step-checkbox"]').exists()).toBe(canTrack);
  wrapper.unmount();
});

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
  const wrapper = render(null, { inPath: false, item: knowledge({ source: null, summary: 'Vienas sakinys.', summaryHtml: '<strong>Vienas</strong> sakinys.', state: 'pending' }) });
  expect(wrapper.find('[data-testid="step-checkbox"]').exists()).toBe(true);
  expect(wrapper.find('[data-slot="step-meta"]').exists()).toBe(false);
  expect(wrapper.get('[data-slot="wiki-prose"] [data-slot="article-summary"]').text()).toBe('Vienas sakinys.');
  expect(wrapper.get('[data-slot="article-summary"] strong').text()).toBe('Vienas');
  await wrapper.setProps({ item: knowledge({ source: null, state: 'done' }) });
  expect(wrapper.get('[data-testid="step-checkbox"]').attributes('aria-checked')).toBe('true');
  expect(wrapper.find('[data-slot="step-meta"]').exists()).toBe(false);
  expect(wrapper.text()).not.toContain('learning.wiki.self_learned_done');
  await wrapper.setProps({ item: knowledge({ source: null, state: 'done', section: { id: 'section', title: 'Studijos' } }) });
  expect(wrapper.get('[data-slot="step-meta"]').text()).toBe('Studijos');
  wrapper.unmount();

  const readonly = render(null, { inPath: false, canTrack: false });
  expect(readonly.find('[data-testid="step-checkbox"]').exists()).toBe(false);
  readonly.unmount();
});

it('gives an off-path source action no path importance', () => {
  const wrapper = render({ ...hiddenDocument, thumbnailUrl: null }, { inPath: false });
  expect(wrapper.findComponent({ name: 'LearningStepAction' }).props('step').importance).toBeNull();
  wrapper.unmount();
});

it('lists related articles in the sidebar without descriptions and marks only learned articles', () => {
  const wrapper = render(null, {
    related: [
      { id: 'learned', title: 'Learned article', abbreviation: 'LA', summary: 'Learned description', url: '/mano/knowledge/items/learned' },
      { id: 'unread', title: 'Unread article', abbreviation: '', summary: 'Unread description', url: '/mano/knowledge/items/unread' },
    ],
    doneIds: ['learned'],
  });
  const related = wrapper.get('[data-slot="step-about"] [data-slot="article-related"]');
  const links = related.findAll('a');

  expect(wrapper.find('article [data-slot="article-related"]').exists()).toBe(false);
  expect(links[0]!.attributes('href')).toBe('/mano/knowledge/items/learned');
  expect(links[0]!.text()).toContain('Learned article');
  expect(links[0]!.text()).toContain('LA');
  expect(links[0]!.get('[data-slot="wiki-page-learned"]').attributes('aria-label')).toBe('learning.wiki.learned');
  expect(links[1]!.find('[data-slot="wiki-page-learned"]').exists()).toBe(false);
  expect(related.text()).not.toContain('Learned description');
  expect(related.text()).not.toContain('Unread description');
  wrapper.unmount();
});

it('omits the related article section when there are no related articles', () => {
  const wrapper = render(null);
  expect(wrapper.find('[data-slot="article-related"]').exists()).toBe(false);
  wrapper.unmount();
});
