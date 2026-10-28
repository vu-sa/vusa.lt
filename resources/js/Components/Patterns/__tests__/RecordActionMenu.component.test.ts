import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { Link } from '@inertiajs/vue3';

import RecordActionMenu from '../RecordActionMenu.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const render = (extra = {}) => mount(RecordActionMenu, {
  props: { actions: [
    { key: 'delete', label: 'Ištrinti', destructive: true },
    { key: 'edit', label: 'Redaguoti', href: '/mano/items/1/edit' },
    { key: 'copy', label: 'Kopijuoti' },
    { key: 'public', label: 'Viešai', href: 'https://vusa.lt', external: true },
    { key: 'busy', label: 'Palauk', loading: true },
  ], ...extra },
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

  it('keeps same-origin non-Inertia links in the current tab', () => {
    const wrapper = render({ actions: [{ key: 'answer', label: 'Atsakyti', href: '/atsakymas/1', plain: true }] });
    expect(wrapper.findComponent(Link).exists()).toBe(false);
    expect(wrapper.get('a').attributes('target')).toBeUndefined();
  });

  it.each(['disabled', 'loading'] as const)('blocks a %s menu link', async (flag) => {
    const wrapper = render({ actions: [{ key: 'answer', label: 'Atsakyti', href: '/atsakymas/1', plain: true, [flag]: true }] });
    const menu = wrapper.get('[data-testid="dropdown-menu-content"]');
    expect(menu.get('[data-action="answer"]').classes()).toEqual(expect.arrayContaining(['pointer-events-none', 'opacity-50']));
    const link = menu.get('a');
    expect(link.attributes('aria-disabled')).toBe('true');
    expect(link.attributes('tabindex')).toBe('-1');
    await link.trigger('click');
    expect(wrapper.emitted('navigate')).toBeUndefined();
  });

  it('emits command keys and keeps loading commands disabled', async () => {
    const wrapper = render();
    const menu = wrapper.get('[data-testid="dropdown-menu-content"]');
    await menu.findAll('button').find(button => button.text() === 'Kopijuoti')!.trigger('click');
    expect(wrapper.emitted('select')).toEqual([['copy']]);
    expect(menu.findAll('button').find(button => button.text() === 'Palauk')!.attributes('disabled')).toBeDefined();
  });
});
