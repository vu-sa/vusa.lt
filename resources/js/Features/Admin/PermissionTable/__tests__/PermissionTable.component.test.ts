import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';

import PermissionTable from '@/Features/Admin/PermissionTable/PermissionTable.vue';
import { commonStubs } from '@/tests/stubs';

const TestIcon = defineComponent({
  name: 'TestIcon',
  template: '<span class="test-icon" />',
});

const iconStubs = {
  IFluentAddCircle24Filled: { template: '<span class="icon-add" />' },
  IFluentEye24Filled: { template: '<span class="icon-eye" />' },
  IFluentEdit24Filled: { template: '<span class="icon-edit" />' },
  IFluentDelete24Filled: { template: '<span class="icon-delete" />' },
  IFluentDeleteForever24Filled: { template: '<span class="icon-delete-forever" />' },
};

describe('PermissionTable', () => {
  let wrapper: ReturnType<typeof mount>;

  const mountTable = (props: {
    permissions?: string[];
    availablePermissions?: string[];
  } = {}) => mount(PermissionTable, {
    props: {
      permissions: props.permissions ?? [],
      role: { id: 1, name: 'Editor' } as unknown as App.Entities.Role,
      modelType: 'news',
      icon: TestIcon,
      availablePermissions: props.availablePermissions ?? [],
    },
    global: {
      stubs: {
        ...commonStubs,
        ...iconStubs,
      },
    },
  });

  const findAbilityRow = (label: string) => {
    const row = wrapper.findAll('tbody tr').find(row => row.text().includes(label));

    expect(row, `Expected row with label "${label}" to exist`).toBeTruthy();

    return row!;
  };

  const findDeleteRow = () => {
    const row = wrapper.findAll('tbody tr').find(row =>
      row.text().includes('Ištrinti') && !row.text().includes('negrįžtamai'),
    );

    expect(row, 'Expected delete row to exist').toBeTruthy();

    return row!;
  };

  const switchesIn = (row: ReturnType<typeof findAbilityRow>) => row.findAll('[role="switch"]');

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  it('does not initialize delete from a forceDelete permission', () => {
    wrapper = mountTable({
      permissions: ['news.forceDelete.*'],
      availablePermissions: [
        'news.delete.padalinys',
        'news.delete.*',
        'news.forceDelete.padalinys',
        'news.forceDelete.*',
      ],
    });

    expect(switchesIn(findDeleteRow()).map(switchControl => switchControl.attributes('data-state')))
      .toEqual(['unchecked', 'unchecked']);
  });

  it('does not initialize forceDelete from a delete permission', () => {
    wrapper = mountTable({
      permissions: ['news.delete.padalinys'],
      availablePermissions: [
        'news.delete.padalinys',
        'news.delete.*',
        'news.forceDelete.padalinys',
        'news.forceDelete.*',
      ],
    });

    expect(switchesIn(findAbilityRow('Ištrinti negrįžtamai')).map(switchControl => switchControl.attributes('data-state')))
      .toEqual(['unchecked', 'unchecked']);
  });

  it('renders forceDelete as unavailable when the model has no forceDelete permissions', () => {
    wrapper = mountTable({
      availablePermissions: [
        'news.delete.padalinys',
        'news.delete.*',
      ],
    });

    const forceDeleteRow = findAbilityRow('Ištrinti negrįžtamai');

    expect(forceDeleteRow.text().match(/Netaikoma/g)).toHaveLength(2);
    expect(forceDeleteRow.find('[role="checkbox"]').exists()).toBe(false);
    expect(forceDeleteRow.find('[role="switch"]').exists()).toBe(false);
  });

  it('offers forceDelete only at padalinys and all scopes', () => {
    wrapper = mountTable({
      availablePermissions: [
        'news.forceDelete.padalinys',
        'news.forceDelete.*',
      ],
    });

    const forceDeleteRow = findAbilityRow('Ištrinti negrįžtamai');

    expect(forceDeleteRow.find('[role="checkbox"]').exists()).toBe(false);
    expect(switchesIn(forceDeleteRow)).toHaveLength(2);
  });

  it('submits the selected forceDelete scope when permissions are updated', async () => {
    wrapper = mountTable({
      availablePermissions: [
        'news.forceDelete.padalinys',
        'news.forceDelete.*',
      ],
    });

    const forceDeleteRow = findAbilityRow('Ištrinti negrįžtamai');
    const forceDeleteSwitches = switchesIn(forceDeleteRow);
    expect(forceDeleteSwitches).toHaveLength(2);
    const allScopeSwitch = forceDeleteSwitches[1];
    if (!allScopeSwitch) {
      throw new Error('Expected forceDelete all-scope switch to exist.');
    }

    await allScopeSwitch.trigger('click');
    await nextTick();

    await wrapper.findAll('button').find(button => button.text().includes('Atnaujinti'))?.trigger('click');

    expect(router.patch).toHaveBeenCalledWith(
      expect.stringContaining('/mocked-route/roles.syncPermissionGroup'),
      { forceDelete: '*' },
      expect.objectContaining({ preserveScroll: true }),
    );
  });
});
