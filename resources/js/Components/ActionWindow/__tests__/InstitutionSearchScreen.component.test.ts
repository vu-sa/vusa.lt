import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h, ref, shallowRef } from 'vue';

import InstitutionSearchScreen from '@/Components/ActionWindow/screens/InstitutionSearchScreen.vue';
import { createActionWindowProvider, type ActionWindowContext } from '@/Composables/useActionWindow';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const institutionSearch = ref({ enabled: true, tenant_ids: [16, 17] });

vi.mock('@/Composables/useActionWindowData', () => ({
  useActionWindowData: () => ({
    institutions: ref([]),
    meetings: ref([]),
    institutionSearch,
    isLoading: ref(false),
    error: ref(null),
    load: vi.fn(),
  }),
  invalidateActionWindowData: vi.fn(),
}));

const searchOptions = vi.fn();
const results = shallowRef<unknown[]>([]);

vi.mock('@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch', () => ({
  useAdminCollectionSearch: (options: unknown) => {
    searchOptions(options);

    return {
      query: ref(''),
      results,
      error: ref(null),
      isSearching: ref(false),
      isLoadingMore: ref(false),
      hasMoreResults: ref(false),
      search: vi.fn(),
      loadMore: vi.fn(),
    };
  },
}));

const mountScreen = () => {
  let window!: ActionWindowContext;

  const wrapper = mount(defineComponent({
    setup() {
      window = createActionWindowProvider();
      window.open({ flow: 'meeting.create' });
      window.goTo('meeting.institution.search');
      return () => h(InstitutionSearchScreen);
    },
  }), { global: { stubs: { ...commonStubs } } });

  return { wrapper, window };
};

describe('InstitutionSearchScreen.vue', () => {
  /**
   * The Typesense key already bounds what the caller may read; this filter narrows it
   * further to the tenants they may actually create a meeting for.
   */
  it('scopes the collection to the tenants the caller may create meetings for', () => {
    institutionSearch.value = { enabled: true, tenant_ids: [16, 17] };
    results.value = [];
    mountScreen();

    expect(searchOptions).toHaveBeenCalledWith(
      expect.objectContaining({ collection: 'institutions', baseFilterBy: 'tenant_ids:[16,17]' }),
    );
  });

  it('sends no filter for an all-scope caller', () => {
    institutionSearch.value = { enabled: true, tenant_ids: [] };
    results.value = [];
    mountScreen();

    expect(searchOptions).toHaveBeenLastCalledWith(
      expect.objectContaining({ baseFilterBy: undefined }),
    );
  });

  it('picking a result files the meeting under it and moves on', async () => {
    institutionSearch.value = { enabled: true, tenant_ids: [16] };
    results.value = [{ id: 'inst-1', name_lt: 'VU SA MIF', tenant_shortname: 'MIF' }];

    const { wrapper, window } = mountScreen();
    await wrapper.findAll('[data-slot="action-choice-button"]')[0]!.trigger('click');

    expect(window.draft.institution).toEqual({ id: 'inst-1', name: 'VU SA MIF' });
    expect(window.draft.meeting.institution_id).toBe('inst-1');
    expect(window.current.value.id).toBe('meeting.type');
  });
});
