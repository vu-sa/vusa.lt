import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { Link } from '@inertiajs/vue3';

import RecordActionMenu from '../RecordActionMenu.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const render = () => mount(RecordActionMenu, {
  props: { actions: [
    { key: 'delete', label: 'Ištrinti', destructive: true },
    { key: 'edit', label: 'Redaguoti', href: '/mano/items/1/edit' },
    { key: 'copy', label: 'Kopijuoti' },
    { key: 'public', label: 'Viešai', href: 'https://vusa.lt', external: true },
    { key: 'busy', label: 'Palauk', loading: true },
  ] },
  global: { stubs: commonStubs },
});

describe('RecordActionMenu', () => {
  it('puts destructive actions last after a separator', () => {
    const wrapper = render();
    const menu = wrapper.get('[data-testid="dropdown-menu-content"]');
    expect(menu.findAll('button').map(button => button.text())).toEqual(['Redaguoti', 'Kopijuoti', 'Viešai', 'Palauk', 'Ištrinti']);
    expect(menu.get('hr').element.nextElementSibling?.textContent).toContain('Ištrinti');
  });

  it('renders internal links with Link and external links with a new tab', () => {
    const wrapper = render();
    expect(wrapper.findComponent(Link).props('href')).toBe('/mano/items/1/edit');
    const external = wrapper.get('a[href="https://vusa.lt"]');
    expect(external.attributes('target')).toBe('_blank');
    expect(external.attributes('rel')).toContain('noopener');
  });

  it('emits command keys and keeps loading commands disabled', async () => {
    const wrapper = render();
    const menu = wrapper.get('[data-testid="dropdown-menu-content"]');
    await menu.findAll('button').find(button => button.text() === 'Kopijuoti')!.trigger('click');
    expect(wrapper.emitted('select')).toEqual([['copy']]);
    expect(menu.findAll('button').find(button => button.text() === 'Palauk')!.attributes('disabled')).toBeDefined();
  });
});
