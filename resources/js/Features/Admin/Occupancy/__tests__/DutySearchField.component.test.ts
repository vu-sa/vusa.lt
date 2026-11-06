import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import DutySearchField from '@/Features/Admin/Occupancy/DutySearchField.vue';
import SearchHitRow from '@/Features/Admin/AdminSearch/Components/SearchHitRow.vue';
import { documentWithMatch } from '@/Shared/Search/matches';
import { commonStubs } from '@/tests/stubs';

const results = ref<unknown[]>([]);
const hasMoreResults = ref(false);
const totalHits = ref(0);
const status = ref('idle');
const loadMore = vi.fn();
const search = vi.fn();

vi.mock('@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch', () => ({
  useAdminCollectionSearch: () => ({
    results,
    hasMoreResults,
    totalHits,
    status,
    isSearching: ref(false),
    search,
    loadMore,
    adminSearch: {
      getCollectionTenantIds: () => [5],
      isSuperAdmin: ref(false),
    },
  }),
}));

const document = {
  id: 'duty-1',
  name_lt: 'Atstovas',
  name_en: 'Representative',
  institution_name_lt: 'Matematikos fakultetas',
  institution_name_en: 'Faculty of Mathematics',
  tenant_shortname: 'MIF',
  home_tenant_id: 5,
  type_ids: [3, 8],
  is_active: true,
  current_user_names: ['Ona Onaitė'],
};

const mountField = () => mount(DutySearchField, {
  props: { modelValue: null },
  global: { stubs: { ...commonStubs, EntityTypeMark: true } },
});

describe('DutySearchField.vue', () => {
  beforeEach(() => {
    results.value = [document];
    hasMoreResults.value = false;
    totalHits.value = 1;
    status.value = 'idle';
    loadMore.mockClear();
    search.mockClear();
  });

  it('only offers more results when another page exists and shows the counts', async () => {
    const wrapper = mountField();
    await wrapper.get('input').setValue('at');

    expect(wrapper.findAll('[role="option"]')).toHaveLength(1);
    expect(wrapper.text()).not.toContain('Rodyti daugiau');

    hasMoreResults.value = true;
    totalHits.value = 9;
    await wrapper.vm.$nextTick();

    expect(wrapper.findAll('[role="option"]')).toHaveLength(2);
    expect(wrapper.findAll('[role="option"]').at(-1)!.text()).toContain('Rodyti daugiau');
  });

  it('loads more on click and blocks another request while loading', async () => {
    hasMoreResults.value = true;
    const wrapper = mountField();
    await wrapper.get('input').setValue('at');
    const more = wrapper.findAll('[role="option"]').at(-1)!;

    await more.trigger('click');
    expect(loadMore).toHaveBeenCalledTimes(1);

    status.value = 'loading-more';
    await wrapper.vm.$nextTick();
    expect(more.attributes('aria-disabled')).toBe('true');
    expect(more.find('.animate-spin').exists()).toBe(true);
    await more.trigger('click');
    expect(loadMore).toHaveBeenCalledTimes(1);
  });

  it('emits the duty payload when a result is chosen', async () => {
    const wrapper = mountField();
    await wrapper.get('input').setValue('at');
    await wrapper.get('[role="option"]').trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([{
      id: 'duty-1',
      name: 'Atstovas',
      institutionName: 'Matematikos fakultetas',
      tenantShortname: 'MIF',
      homeTenantId: 5,
      typeIds: [3, 8],
      isActive: true,
    }]);
  });

  it('shows current holders and forwards raw highlights to the shared row without a chevron', async () => {
    results.value = [documentWithMatch({ document, highlights: [
      { field: 'name_lt', snippet: '⟦Atstovas⟧' },
      { field: 'institution_name_lt', snippet: '⟦Matematikos⟧ fakultetas' },
    ] })];
    const wrapper = mountField();
    await wrapper.get('input').setValue('atstovas matematikos');

    const row = wrapper.getComponent(SearchHitRow);
    expect(row.props('hit').raw).toEqual(results.value[0]);
    expect(row.props('trailing')).toBe('none');
    expect(row.text()).toContain('Ona Onaitė');
    expect(row.find('[data-field="institution_name_lt"] mark').text()).toBe('Matematikos');
    expect(row.findAll('mark').some(mark => mark.text().includes('Atstov'))).toBe(true);
  });

  it('reaches show more with the arrows and loads it with Enter', async () => {
    hasMoreResults.value = true;
    const wrapper = mountField();
    const input = wrapper.get('input');
    await input.setValue('at');
    await input.trigger('keydown', { key: 'ArrowUp' });

    const more = wrapper.findAll('[role="option"]').at(-1)!;
    expect(input.attributes('aria-activedescendant')).toBe(more.attributes('id'));
    await input.trigger('keydown', { key: 'Enter' });
    expect(loadMore).toHaveBeenCalledTimes(1);
    expect(wrapper.emitted('update:modelValue')).toBeUndefined();

    await input.trigger('keydown', { key: 'ArrowUp' });
    expect(wrapper.findAll('[role="option"]')[0].attributes('aria-selected')).toBe('true');
    await input.trigger('keydown', { key: 'ArrowDown' });
    expect(more.attributes('aria-selected')).toBe('true');
  });

  it('keeps the active duty when another page is appended', async () => {
    hasMoreResults.value = true;
    const wrapper = mountField();
    const input = wrapper.get('input');
    await input.setValue('at');
    await input.trigger('keydown', { key: 'ArrowDown' });
    const activeId = input.attributes('aria-activedescendant');

    results.value = [...results.value, { ...document, id: 'duty-2' }];
    await wrapper.vm.$nextTick();

    expect(input.attributes('aria-activedescendant')).toBe(activeId);
    expect(wrapper.findAll('[role="option"]')[0].attributes('aria-selected')).toBe('true');
    await input.trigger('keydown', { key: 'Enter' });
    expect(wrapper.emitted('update:modelValue')?.[0]?.[0]).toMatchObject({ id: 'duty-1' });
  });
});
