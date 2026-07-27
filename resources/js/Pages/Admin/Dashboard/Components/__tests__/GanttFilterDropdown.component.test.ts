import { afterEach, describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import GanttFilterDropdown from '../GanttFilterDropdown.vue';

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
  { id: 1, shortname: 'VU SA IF' },
  { id: 2, shortname: 'VU SA FF' },
];

describe('GanttFilterDropdown', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  function createWrapper(props: Partial<InstanceType<typeof GanttFilterDropdown>['$props']> = {}) {
    return mount(GanttFilterDropdown, {
      props: {
        showOnlyWithActivity: false,
        showOnlyWithPublicMeetings: false,
        showDutyMembers: true,
        showTenantHeaders: false,
        tenants,
        selectedTenants: ['1'],
        ...props,
      },
      global: {
        stubs: dropdownStubs,
      },
    });
  }

  // Regression: isFinalSelectedTenant() used to be declared inside toggleTenant's
  // else-branch, unreachable from the template — rendering with a non-empty
  // `tenants` list threw a ReferenceError for every non-admin user.
  it('renders tenant checkboxes without throwing when tenants are provided', () => {
    expect(() => {
      wrapper = createWrapper();
    }).not.toThrow();

    expect(wrapper.findAll('[role="menuitemcheckbox"]').length).toBeGreaterThanOrEqual(2);
  });

  it('disables the last selected tenant only when a selection is required', () => {
    wrapper = createWrapper({ requireTenantSelection: true, selectedTenants: ['1'] });

    const tenantItems = wrapper.findAll('[role="menuitemcheckbox"]');
    expect(tenantItems[0].attributes('disabled')).toBeDefined();
    expect(tenantItems[1].attributes('disabled')).toBeUndefined();
  });

  it('toggles a tenant on and off', async () => {
    wrapper = createWrapper({ selectedTenants: ['1'] });

    await wrapper.findAll('[role="menuitemcheckbox"]')[1].trigger('click');
    expect(wrapper.emitted('update:selectedTenants')?.[0]).toEqual([['1', '2']]);
  });

  it('clears the tenant selection via the "Joks" quick action', async () => {
    wrapper = createWrapper({ selectedTenants: ['1', '2'] });

    const clearButton = wrapper.findAll('button').find(button => button.text() === 'Joks');
    await clearButton?.trigger('click');

    expect(wrapper.emitted('update:selectedTenants')?.[0]).toEqual([[]]);
  });
});
