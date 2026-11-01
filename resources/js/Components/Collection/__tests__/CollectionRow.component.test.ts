import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import CollectionRowActions from '../CollectionRowActions.vue';

import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

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
