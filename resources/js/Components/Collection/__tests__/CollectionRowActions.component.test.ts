import { mount } from '@vue/test-utils';
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Pencil, RefreshCw, Trash2 } from '@lucide/vue';
import { describe, expect, it, vi } from 'vitest';

import RecordActionMenu from '@/Components/Patterns/RecordActionMenu.vue';
import { commonStubs } from '@/tests/stubs';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';

import CollectionRowActions from '../CollectionRowActions.vue';

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

  it('uses the row action styling and a single labelled action in previews', async () => {
    const list = actions.map(action => action.role === 'open' ? { ...action, primary: true } : action);
    const wrapper = render(list);
    const rowActionClasses = wrapper.get('[data-action="open"]').classes();

    await wrapper.setProps({ layout: 'preview', fullLabels: true });

    expect(wrapper.get('[data-action="open"]').classes()).toEqual(rowActionClasses);
    expect(rowActionClasses).toContain('bg-(--button-outline-fill)');
    expect(wrapper.findAll('a')).toHaveLength(1);
    expect(wrapper.getComponent(RecordActionMenu).props('actions')).toEqual(list.slice(1));
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

  it('uses plain same-tab links when requested', () => {
    const wrapper = render([{ key: 'answer', label: 'Atsakyti', role: 'open', href: '/atsakymas/1', plain: true }]);
    expect(wrapper.findComponent(Link).exists()).toBe(false);
    expect(wrapper.get('a').attributes('target')).toBeUndefined();
  });

  it.each(['disabled', 'loading'] as const)('gives a %s link disabled semantics and styling', async (flag) => {
    const wrapper = render([{ ...actions[0], [flag]: true }]);
    const link = wrapper.get('a');
    expect(link.attributes('aria-disabled')).toBe('true');
    expect(link.classes()).toEqual(expect.arrayContaining(['pointer-events-none', 'opacity-50']));
    await link.trigger('click');
    expect(wrapper.emitted('navigate')).toBeUndefined();
  });

  it('keeps an embedded edit command without a role in the menu', () => {
    const wrapper = render([{ key: 'edit', label: 'Redaguoti' }]);
    expect(wrapper.find('button').exists()).toBe(false);
    expect(wrapper.findComponent(RecordActionMenu).props('actions')[0].key).toBe('edit');
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

const mountActions = (actions: AdminRecordAction[], foldPrimary: boolean) => mount(CollectionRowActions, {
  props: { actions, stacked: true, foldPrimary },
  global: { stubs: commonStubs },
});

const open: AdminRecordAction = { key: 'open', label: 'Atidaryti', role: 'open' };
const primary: AdminRecordAction = { key: 'primary', label: 'Užregistruoti naują posėdį', shortLabel: 'Pildyti', primary: true };
const remove: AdminRecordAction = { key: 'delete', label: 'Ištrinti', destructive: true };
const menu = '[data-testid="dropdown-menu-content"]';

describe('stacked row actions', () => {
  it('shows a primary open action on a wide row and leaves it to the title on a narrow one', () => {
    const primaryOpen = { ...open, primary: true };
    const edit: AdminRecordAction = { key: 'edit', label: 'Redaguoti', role: 'edit', menuOnly: true };

    expect(mountActions([primaryOpen, edit], false).get('[data-collection-row-action="primary"]').text()).toBe('Atidaryti');
    expect(mountActions([primaryOpen, edit], false).find(`${menu} [data-action="edit"]`).exists()).toBe(true);

    const narrow = mountActions([primaryOpen, edit], true);
    expect(narrow.find('[data-action="open"]').exists()).toBe(false);
    expect(narrow.find(`${menu} [data-action="edit"]`).exists()).toBe(true);
  });

  it('shows a wide row the primary action with its full label, beside the menu', () => {
    const wrapper = mountActions([open, primary, remove], false);

    expect(wrapper.get('[data-collection-row-action="primary"]').text()).toBe('Užregistruoti naują posėdį');
    expect(wrapper.find(`${menu} [data-action="primary"]`).exists()).toBe(false);
  });

  it('folds the primary action to the top of the menu on a narrow row', () => {
    const wrapper = mountActions([open, primary, remove], true);

    expect(wrapper.find('[data-collection-row-action="primary"]').exists()).toBe(false);
    expect(wrapper.findAll(`${menu} [data-action]`).map(item => item.attributes('data-action'))).toEqual(['primary', 'delete']);
  });

  it('leaves opening to the title, and a wide row keeps the edit button', () => {
    const wrapper = mountActions([open, { key: 'edit', label: 'Redaguoti', role: 'edit' }], false);

    expect(wrapper.find('[data-action="open"]').exists()).toBe(false);
    expect(wrapper.get('[data-collection-row-action="primary"]').text()).toBe('Redaguoti');
  });

  it('folds the edit button into the menu on a narrow row', () => {
    const wrapper = mountActions([open, { key: 'edit', label: 'Redaguoti', role: 'edit' }], true);

    expect(wrapper.find('[data-collection-row-action="primary"]').exists()).toBe(false);
    expect(wrapper.find(`${menu} [data-action="edit"]`).exists()).toBe(true);
  });
});
