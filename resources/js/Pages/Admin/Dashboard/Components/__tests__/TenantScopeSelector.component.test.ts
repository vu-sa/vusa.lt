import { afterEach, describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import TenantScopeSelector from '../TenantScopeSelector.vue';

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

const tenants = [
  { id: 1, shortname: 'VU SA IF', type: 'padalinys' },
  { id: 2, shortname: 'VU SA FF', type: 'padalinys' },
];

describe('TenantScopeSelector', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  function createWrapper(selectedTenants: string[]) {
    return mount(TenantScopeSelector, {
      props: {
        tenants,
        selectedTenants,
      },
      global: {
        stubs: dropdownStubs,
      },
    });
  }

  it('shows selected and total tenant counts', () => {
    wrapper = createWrapper(['1']);

    expect(wrapper.get('[data-testid="tenant-scope-trigger"]').text()).toContain('1/2');
  });

  it('prevents deselecting the final tenant', () => {
    wrapper = createWrapper(['1']);

    const tenantItems = wrapper.findAll('[role="menuitemcheckbox"]');

    expect(tenantItems[0].attributes('disabled')).toBeDefined();
    expect(tenantItems[1].attributes('disabled')).toBeUndefined();
  });

  it('emits an ordered multi-tenant selection', async () => {
    wrapper = createWrapper(['1']);

    await wrapper.findAll('[role="menuitemcheckbox"]')[1].trigger('click');

    expect(wrapper.emitted('update:selectedTenants')?.[0]).toEqual([['1', '2']]);
    expect(wrapper.emitted('engage')).toHaveLength(1);
  });

  it('selects all tenants from the quick action', async () => {
    wrapper = createWrapper(['1']);

    const selectAllButton = wrapper.findAll('button').find(button => button.text() === 'Visi');
    await selectAllButton?.trigger('click');

    expect(wrapper.emitted('update:selectedTenants')?.[0]).toEqual([['1', '2']]);
  });
});
