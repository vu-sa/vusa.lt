import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, expect, it, vi } from 'vitest';

import ShowKnowledgeSection from '@/Pages/Admin/Knowledge/ShowKnowledgeSection.vue';
import { KnowledgeItemKind, KnowledgeSectionKind } from '@/Types/enums';
import type { WikiPageSummary } from '@/Types/learning';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const page = (id: string, title: string): WikiPageSummary => ({
  id, kind: KnowledgeItemKind.Material, title, abbreviation: '', summary: '', summaryHtml: '', url: `/mano/knowledge/items/${id}`, thumbnailUrl: null,
});
const section = { id: 'training', kind: KnowledgeSectionKind.Collection, title: 'Įvadiniai mokymai', description: '', date: '2026-09-20', order: 0 };

const editing = { options: [], scopes: { create: { canTargetEverywhere: true, tenants: [] }, update: { canTargetEverywhere: true, tenants: [] } } };

function render(canReorder: boolean) {
  return mount(ShowKnowledgeSection, {
    props: { section, pages: [page('a', 'Skaidrės'), page('b', 'Užduotys')], doneIds: [], canReorder, editing: canReorder ? editing : null },
    global: { stubs: {
      ...commonStubs, Popover: true, PopoverAnchor: true, PopoverContent: true,
      LearningSectionsSheet: { props: ['open', 'editOnly'], template: '<div data-testid="section-sheet" :data-open="open" :data-edit-only="editOnly" />' },
    } },
  });
}

beforeEach(() => vi.clearAllMocks());

it('reorders the articles in a mode and saves their ids in the new order', async () => {
  const wrapper = render(true);
  expect(wrapper.get('[data-slot="section-meta"] time').attributes('datetime')).toBe('2026-09-20');
  await wrapper.get('[data-testid="reorder-section-items"]').trigger('click');
  expect(wrapper.get('[data-testid="save-section-order"]').attributes('disabled')).toBeDefined();

  await wrapper.get('[data-slot="section-reorder"] button[aria-label="Perkelti žemiau"]').trigger('click');
  await wrapper.get('[data-testid="save-section-order"]').trigger('click');
  expect(router.post).toHaveBeenCalledWith(expect.stringContaining('knowledgeSections.items.reorder'), { items: ['b', 'a'] }, expect.any(Object));
  wrapper.unmount();
});

it('opens this section in the Skyriai editor from its own page', async () => {
  const wrapper = render(true);
  await wrapper.get('[data-testid="edit-section"]').trigger('click');
  expect(wrapper.get('[data-testid="section-sheet"]').attributes()).toMatchObject({ 'data-open': 'true', 'data-edit-only': 'training' });
  wrapper.unmount();
});

it('offers no reordering or editing to readers', () => {
  const wrapper = render(false);
  expect(wrapper.find('[data-testid="reorder-section-items"]').exists()).toBe(false);
  expect(wrapper.find('[data-testid="edit-section"]').exists()).toBe(false);
  expect(wrapper.findAll('[data-slot="wiki-page-list"] li')).toHaveLength(2);
  wrapper.unmount();
});
