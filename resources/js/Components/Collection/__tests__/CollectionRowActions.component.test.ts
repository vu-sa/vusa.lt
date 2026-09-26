import { mount } from '@vue/test-utils';
import { Pencil, RefreshCw, Trash2 } from 'lucide-vue-next';
import { describe, expect, it, vi } from 'vitest';

import CollectionRowActions from '../CollectionRowActions.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('CollectionRowActions', () => {
  it('names icon actions and keeps the visible edit label', async () => {
    const wrapper = mount(CollectionRowActions, {
      props: {
        actions: [
          { key: 'edit', label: 'Redaguoti', icon: Pencil, labelled: true, href: '/mano/items/1/edit' },
          { key: 'delete', label: 'Ištrinti', icon: Trash2, destructive: true },
        ],
      },
    });

    expect(wrapper.find('a').attributes('href')).toBe('/mano/items/1/edit');
    expect(wrapper.find('a').text()).toBe('Redaguoti');
    expect(wrapper.find('button').attributes('aria-label')).toBe('Ištrinti');
    expect(wrapper.find('button').classes()).toContain('pointer-coarse:size-11');

    await wrapper.find('button').trigger('click');
    expect(wrapper.emitted('select')).toEqual([['delete']]);
  });

  it('blocks a loading command and exposes its busy state', async () => {
    const wrapper = mount(CollectionRowActions, {
      props: { actions: [{ key: 'refresh', label: 'Atnaujinti iš SharePoint', icon: RefreshCw, loading: true }] },
    });

    const button = wrapper.find('button');
    expect(button.attributes('disabled')).toBeDefined();
    expect(button.attributes('aria-busy')).toBe('true');
    expect(button.find('svg').classes()).toContain('animate-spin');

    await button.trigger('click');
    expect(wrapper.emitted('select')).toBeUndefined();
  });
});
