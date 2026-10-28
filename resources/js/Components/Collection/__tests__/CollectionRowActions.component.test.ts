import { mount } from '@vue/test-utils';
import { ArrowUpRight, Pencil, RefreshCw, Trash2 } from '@lucide/vue';
import { describe, expect, it, vi } from 'vitest';

import CollectionRowActions from '../CollectionRowActions.vue';
import RecordActionMenu from '@/Components/Patterns/RecordActionMenu.vue';
import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const actions: AdminRecordAction[] = [
  { key: 'open', label: 'Atidaryti', role: 'open', icon: ArrowUpRight, href: '/mano/items/1' },
  { key: 'edit', label: 'Redaguoti', role: 'edit', icon: Pencil, href: '/mano/items/1/edit' },
  { key: 'delete', label: 'Ištrinti', icon: Trash2, destructive: true },
  { key: 'refresh', label: 'Atnaujinti', icon: RefreshCw },
];
const render = (list = actions) => mount(CollectionRowActions, {
  props: { actions: list },
  global: { stubs: { RecordActionMenu: true } },
});

describe('CollectionRowActions', () => {
  it('labels edit by default, names the open icon, and puts other actions in the menu', () => {
    const wrapper = render();
    expect(wrapper.get('a[href="/mano/items/1/edit"]').text()).toBe('Redaguoti');
    const open = wrapper.get('a[href="/mano/items/1"]');
    expect(open.text()).toBe('');
    expect(open.attributes('aria-label')).toBe('Atidaryti');
    expect(open.attributes('title')).toBe('Atidaryti');
    expect(open.classes()).toContain('pointer-coarse:size-11');
    expect(wrapper.findComponent(RecordActionMenu).props('actions').map((action: AdminRecordAction) => action.key)).toEqual(['delete', 'refresh']);
  });

  it('gives the primary action the short label and keeps open then edit as icons', async () => {
    const wrapper = render([...actions, { key: 'agenda', label: 'Atidaryti darbotvarkę', shortLabel: 'Darbotvarkė', primary: true }]);
    expect(wrapper.get('button').text()).toBe('Darbotvarkė');
    expect(wrapper.findAll('a').map(link => link.attributes('aria-label'))).toEqual(['Atidaryti', 'Redaguoti']);
    await wrapper.get('button').trigger('click');
    expect(wrapper.emitted('select')).toEqual([['agenda']]);
    await wrapper.setProps({ fullLabels: true });
    expect(wrapper.get('button').text()).toBe('Atidaryti darbotvarkę');
  });

  it('keeps even one secondary command in the menu and forwards selection', () => {
    const wrapper = render([actions[3]]);
    expect(wrapper.find('button').exists()).toBe(false);
    wrapper.findComponent(RecordActionMenu).vm.$emit('select', 'refresh');
    expect(wrapper.emitted('select')).toEqual([['refresh']]);
  });

  it('blocks a loading primary command and exposes its busy state', () => {
    const wrapper = render([{ key: 'refresh', label: 'Atnaujinti', icon: RefreshCw, primary: true, loading: true }]);
    expect(wrapper.get('button').attributes('disabled')).toBeDefined();
    expect(wrapper.get('button').attributes('aria-busy')).toBe('true');
    expect(wrapper.get('svg').classes()).toContain('animate-spin');
  });

  it('labels a restore declared primary and keeps other restore commands in the menu', () => {
    expect(render([{ key: 'restore', label: 'Atkurti', primary: true }]).get('button').text()).toBe('Atkurti');
    const wrapper = render([{ key: 'restore', label: 'Atkurti' }, actions[2]]);
    expect(wrapper.find('button').exists()).toBe(false);
    expect(wrapper.findComponent(RecordActionMenu).props('actions')).toHaveLength(2);
    const loneCommand = render([{ key: 'restore', label: 'Atkurti' }]);
    expect(loneCommand.find('button').exists()).toBe(false);
    expect(loneCommand.findComponent(RecordActionMenu).props('actions')).toHaveLength(1);
  });
});
