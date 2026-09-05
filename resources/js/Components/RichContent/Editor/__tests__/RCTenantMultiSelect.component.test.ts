import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import { usePage } from '@inertiajs/vue3';
import RCTenantMultiSelect from '../RCTenantMultiSelect.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

// The shared `tenants` mock fixture (inertia.mock.ts) uses string ids for an unrelated
// consumer (the tenant switcher) — this component's contract is `App.Entities.Tenant`'s
// real numeric `id`, so override with a numeric-id fixture rather than relying on that.
vi.mocked(usePage).mockReturnValue(createMockPage({
  tenants: [
    { id: 1, shortname: 'VU SA IF' },
    { id: 2, shortname: 'VU SA FF' },
    { id: 3, shortname: 'VU SA MIF' },
  ],
}));

const dropdownStubs = {
  DropdownMenu: { template: '<div><slot /></div>' },
  DropdownMenuTrigger: { template: '<div><slot /></div>' },
  DropdownMenuContent: { template: '<div><slot /></div>' },
  DropdownMenuLabel: { template: '<div><slot /></div>' },
  DropdownMenuSeparator: { template: '<hr />' },
  DropdownMenuCheckboxItem: {
    props: ['modelValue', 'disabled'],
    emits: ['update:modelValue'],
    template: `<button
      type="button"
      role="menuitemcheckbox"
      :disabled="disabled"
      :data-checked="String(modelValue)"
      @click="$emit('update:modelValue', !modelValue)"
    ><slot /></button>`,
  },
};

function mountSelect(modelValue: 'all' | number[] | undefined) {
  return mount(RCTenantMultiSelect, {
    props: { modelValue },
    global: { stubs: dropdownStubs },
  });
}

describe('RCTenantMultiSelect', () => {
  it('treats "all" and unset the same — every tenant checked, trigger reads "all"', () => {
    const wrapper = mountSelect('all');
    const items = wrapper.findAll('[role="menuitemcheckbox"]');
    expect(items).toHaveLength(3);
    expect(items.every(item => item.attributes('data-checked') === 'true')).toBe(true);
    expect(wrapper.get('button').text()).toContain('tenant_scope_all');
  });

  it('"None" quick action emits an empty array, not "all"', async () => {
    const wrapper = mountSelect('all');
    const noneButton = wrapper.findAll('button').find(b => b.text().includes('tenant_scope_none'));
    await noneButton!.trigger('click');

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([[]]);
  });

  it('"All" quick action emits the "all" sentinel, not an exhaustive id list', async () => {
    const wrapper = mountSelect([]);
    const allButton = wrapper.findAll('button').find(b => b.text().includes('tenant_scope_all'));
    await allButton!.trigger('click');

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['all']);
  });

  it('unchecking one tenant while "all" is active materializes every other tenant, not a negative selection', async () => {
    const wrapper = mountSelect('all');
    const items = wrapper.findAll('[role="menuitemcheckbox"]');
    await items[0].trigger('click'); // uncheck tenant id 1

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([[2, 3]]);
  });

  it('checking a tenant while some are selected adds it to the array', async () => {
    const wrapper = mountSelect([2]);
    const items = wrapper.findAll('[role="menuitemcheckbox"]');
    await items[0].trigger('click'); // check tenant id 1

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([[2, 1]]);
  });

  it('shows the single selected tenant\'s shortname when exactly one is picked', () => {
    const wrapper = mountSelect([1]);
    expect(wrapper.get('button').text()).toContain('VU SA IF');
  });

  it('shows a selected-count summary for a genuine partial selection', () => {
    const wrapper = mountSelect([1, 2]);
    expect(wrapper.get('button').text()).toContain('tenant_scope_selected_count');
  });
});
