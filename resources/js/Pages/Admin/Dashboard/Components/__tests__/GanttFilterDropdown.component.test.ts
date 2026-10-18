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
        ...props,
      },
      global: {
        stubs: dropdownStubs,
      },
    });
  }

  it('holds display settings only: padaliniai and grouping have their own controls', () => {
    wrapper = createWrapper();

    expect(wrapper.text()).not.toContain('Padaliniai');
    expect(wrapper.text()).not.toContain('Rodyti padalinių sekcijas');
  });

  it('offers hiding VU SA\'s own bodies, unticked', async () => {
    wrapper = createWrapper();

    const items = wrapper.findAll('[role="menuitemcheckbox"]');
    const hideItem = items.find(item => item.text() === 'Slėpti VU SA darinius');

    expect(hideItem).toBeDefined();
    expect(hideItem!.attributes('aria-checked')).not.toBe('true');

    await hideItem!.trigger('click');
    expect(wrapper.emitted('update:hideInternalInstitutions')?.[0]).toEqual([true]);
  });

  it('offers the activity-request toggle only to people who may see requests', async () => {
    wrapper = createWrapper();
    expect(wrapper.find('[data-slot="gantt-activity-requests-toggle"]').exists()).toBe(false);
    wrapper.unmount();

    wrapper = createWrapper({ showActivityRequestsOption: true, showActivityRequests: true });
    const toggle = wrapper.get('[data-slot="gantt-activity-requests-toggle"]');
    expect(toggle.attributes('data-checked')).toBe('true');

    await toggle.trigger('click');
    expect(wrapper.emitted('update:showActivityRequests')?.[0]).toEqual([false]);
  });
});
