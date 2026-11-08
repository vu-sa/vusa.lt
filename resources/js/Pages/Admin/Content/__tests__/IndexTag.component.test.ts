import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import IndexTag from '../IndexTag.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useAdminNavigation', () => ({ useAdminNavigation: () => ({ hasCollectionAction: () => false }) }));
const sourceSpies = vi.hoisted(() => ({ refresh: vi.fn() }));
vi.mock('@/Composables/useCollectionSource', () => ({
  useDatabaseCollectionSource: (options: { initial: { items: { id: string }[] } }) => {
    const items = ref(options.initial.items);
    return {
      items,
      hideItems: (ids: string[]) => {
        const before = items.value;
        items.value = items.value.filter(item => !ids.includes(String(item.id)));
        return () => { items.value = before; };
      },
      refresh: sourceSpies.refresh,
    };
  },
}));

const tag = { id: 'tag-1', name: { lt: 'Žyma', en: 'Tag' } };
function mountPage() {
  return mount(IndexTag, {
    props: {
      abilities: { update: true, delete: true }, deletedCount: 0,
      tags: { data: [tag], meta: { total: 1, per_page: 50, current_page: 1, last_page: 1 } },
    } as never,
    global: { stubs: {
      CollectionPage: { name: 'CollectionPage', props: ['source'], template: '<div />' },
      TagSheetForm: true, MergeRecordsDialog: true, ConfirmDialog: true,
    } },
  });
}

describe('tag deletion', () => {
  beforeEach(() => vi.clearAllMocks());

  it('deletes immediately and refreshes the source after success to reconcile its total', () => {
    const wrapper = mountPage();
    const collection = wrapper.findComponent({ name: 'CollectionPage' });
    collection.vm.$emit('rowAction', tag, 'delete');
    expect(collection.props('source').items.value).toEqual([]);
    expect(sourceSpies.refresh).not.toHaveBeenCalled();
    expect(router.delete).toHaveBeenCalledWith(route('tags.destroy', tag.id), expect.objectContaining({ preserveScroll: true }));
    vi.mocked(router.delete).mock.calls.at(-1)?.[1].onSuccess(createMockPage());
    expect(sourceSpies.refresh).toHaveBeenCalledOnce();
    wrapper.unmount();
  });

  it('shows the row again when the toast undo restores the tag', () => {
    const success = vi.spyOn(toast, 'success');
    const wrapper = mountPage();
    const collection = wrapper.findComponent({ name: 'CollectionPage' });
    collection.vm.$emit('rowAction', tag, 'delete');
    vi.mocked(router.delete).mock.calls.at(-1)?.[1]?.onSuccess?.(createMockPage(), {} as never);
    const action = success.mock.calls.at(-1)?.[1]?.action as { onClick: (event: MouseEvent) => void };
    action.onClick(new MouseEvent('click'));
    expect(router.patch).toHaveBeenCalledWith(route('tags.restore', tag.id), {}, expect.anything());
    vi.mocked(router.patch).mock.calls.at(-1)?.[2]?.onSuccess?.(createMockPage(), {} as never);
    expect(collection.props('source').items.value).toEqual([tag]);
    wrapper.unmount();
  });

  it('restores the optimistic row when deletion fails', () => {
    const wrapper = mountPage();
    const collection = wrapper.findComponent({ name: 'CollectionPage' });
    collection.vm.$emit('rowAction', tag, 'delete');
    vi.mocked(router.delete).mock.calls.at(-1)?.[1].onError({ name: 'Invalid' });
    expect(collection.props('source').items.value).toEqual([tag]);
    expect(sourceSpies.refresh).not.toHaveBeenCalled();
    wrapper.unmount();
  });
});

it('keeps the preview row free of actions and tags while the detail card carries the editor action', () => {
  const wrapper = mount(IndexTag, {
    props: {
      abilities: { update: true, delete: true }, deletedCount: 0,
      tags: { data: [{ ...tag, is_topic: true }], meta: { total: 1, per_page: 50, current_page: 1, last_page: 1 } },
    } as never,
    global: { stubs: {
      CollectionPage: {
        props: ['source', 'rowActions'],
        template: `<div>
          <section data-testid="preview-row"><slot name="row" :item="source.items.value[0]" view="preview" :actions="rowActions(source.items.value[0])" /></section>
          <slot name="preview" :item="source.items.value[0]" :actions="rowActions(source.items.value[0])" />
        </div>`,
      },
      CollectionRow: { name: 'CollectionRow', props: ['actions'], template: '<article><slot /></article>' },
      CollectionRowActions: true,
      TagPreview: { template: '<div><slot name="actions" /></div>' },
      TagSheetForm: true, MergeRecordsDialog: true, ConfirmDialog: true,
    } },
  });
  expect(wrapper.findComponent({ name: 'CollectionRow' }).props('actions')).toEqual([]);
  expect(wrapper.get('[data-testid="preview-row"]').text()).not.toContain('Teminė');
  expect(wrapper.findComponent({ name: 'CollectionRowActions' }).props()).toMatchObject({ emphasis: true, fullLabels: true });
  wrapper.unmount();
});
