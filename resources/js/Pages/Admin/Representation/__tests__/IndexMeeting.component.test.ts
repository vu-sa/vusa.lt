import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import IndexMeeting from '../IndexMeeting.vue';

import { commonStubs } from '@/tests/stubs';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const rows = vi.hoisted(() => ({ items: [] as Record<string, unknown>[], refresh: vi.fn() }));

vi.mock('@/Composables/useCollectionSource', () => ({
  isTrashView: () => true,
  useTypesenseCollectionSource: vi.fn(),
  useTrashCollectionSource: () => {
    const items = ref(rows.items);
    rows.refresh.mockImplementation(() => { items.value = []; });
    return { items, refresh: rows.refresh };
  },
}));

function mountPage() {
  return mount(IndexMeeting, {
    props: { deletedCount: rows.items.length, recentlyChanged: [], defaultTenantShortnames: [] },
    global: { stubs: {
      ...commonStubs,
      CollectionConfirmAction: true,
      CollectionPage: {
        props: ['source', 'rowActions'],
        template: '<div><div v-for="item in source.items.value" :key="item.id" :data-row="item.id"><slot name="row" :item="item" :actions="rowActions?.(item) ?? []" /></div></div>',
      },
    } },
  });
}

describe('meeting trash actions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: { create: { meeting: false } } } }));
  });

  it('restores an eligible row without requiring creation permission', async () => {
    rows.items = [{ id: 'm1', title: 'Posėdis', abilities: { restore: true, forceDelete: false } }];
    const wrapper = mountPage();
    const button = wrapper.get('[data-row="m1"] button');

    expect(button.text()).toBe('Atkurti');
    await button.trigger('click');
    expect(router.patch).toHaveBeenCalledWith(expect.stringContaining('meetings.restore'), {}, expect.objectContaining({ preserveScroll: true }));
    vi.mocked(router.patch).mock.calls.at(-1)?.[2].onSuccess();
    await wrapper.vm.$nextTick();
    expect(wrapper.find('[data-row="m1"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it('does not offer restoration based on creation permission or another rows abilities', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: { create: { meeting: true } } } }));
    rows.items = [
      { id: 'allowed', title: 'Posėdis', abilities: { restore: true, forceDelete: false } },
      { id: 'denied', title: 'Posėdis', abilities: { restore: false, forceDelete: false } },
    ];
    const wrapper = mountPage();

    expect(wrapper.find('[data-row="allowed"] button').exists()).toBe(true);
    expect(wrapper.find('[data-row="denied"] button').exists()).toBe(false);
  });
});
