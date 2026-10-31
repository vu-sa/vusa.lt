import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import IndexTag from '../IndexTag.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useAdminNavigation', () => ({ useAdminNavigation: () => ({ hasCollectionAction: () => false }) }));
const sourceSpies = vi.hoisted(() => ({ refresh: vi.fn() }));
vi.mock('@/Composables/useCollectionSource', () => ({
  useDatabaseCollectionSource: (options: { initial: { items: unknown[] } }) => {
    const items = ref(options.initial.items);
    return { items, replaceItems: (next: unknown[]) => { items.value = next; }, refresh: sourceSpies.refresh };
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
    vi.mocked(router.delete).mock.calls.at(-1)?.[1].onSuccess();
    expect(sourceSpies.refresh).toHaveBeenCalledOnce();
    wrapper.unmount();
  });

  it('restores the optimistic row when deletion fails', () => {
    const wrapper = mountPage();
    const collection = wrapper.findComponent({ name: 'CollectionPage' });
    collection.vm.$emit('rowAction', tag, 'delete');
    vi.mocked(router.delete).mock.calls.at(-1)?.[1].onError();
    expect(collection.props('source').items.value).toEqual([tag]);
    expect(sourceSpies.refresh).not.toHaveBeenCalled();
    wrapper.unmount();
  });
});
