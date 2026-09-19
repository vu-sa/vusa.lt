import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

import ShellBreadcrumbs from '../ShellBreadcrumbs.vue';

import { atstovavimas } from './fixtures';

import type { BreadcrumbItem } from '@/Composables/useBreadcrumbsUnified';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const trailRef = ref<BreadcrumbItem[]>([]);

vi.mock('@/Composables/useBreadcrumbsUnified', () => ({
  useBreadcrumbs: () => ({ breadcrumbs: trailRef }),
}));

const crumb = (label: string, routeName?: string): BreadcrumbItem => ({
  label,
  href: routeName ? route(routeName) : undefined,
});

const meetings = atstovavimas.sections[1];

const mountTrail = (items: BreadcrumbItem[]) => {
  trailRef.value = items;

  return mount(ShellBreadcrumbs, { props: { activeSection: meetings } });
};

describe('ShellBreadcrumbs', () => {
  beforeEach(() => {
    trailRef.value = [];
  });

  it('renders nothing on a section index', () => {
    const wrapper = mountTrail([crumb('Pradinis', 'dashboard'), crumb('Administravimas', 'administration'), crumb('Posėdžiai')]);

    expect(wrapper.find('nav').exists()).toBe(false);
  });

  it('renders the section and what is below it, without Pradinis and Administravimas', () => {
    const wrapper = mountTrail([
      crumb('Pradinis', 'dashboard'),
      crumb('Administravimas', 'administration'),
      crumb('Posėdžiai', 'meetings.index'),
      crumb('2026-09-01'),
    ]);
    const items = wrapper.findAll('ol li:not([aria-hidden])');

    expect(items.map(item => item.text())).toEqual(['Posėdžiai', '2026-09-01']);
    expect(items[0].find('a').exists()).toBe(true);
    expect(items[1].find('a').exists()).toBe(false);
    expect(items[1].find('[aria-current="page"]').exists()).toBe(true);
  });

  it('offers the nearest linked ancestor as the way back on a phone', () => {
    const wrapper = mountTrail([
      crumb('Posėdžiai', 'meetings.index'),
      crumb('Posėdis', 'meetings.show'),
      crumb('Klausimas'),
    ]);
    const back = wrapper.find('a.md\\:hidden');

    expect(back.find('.truncate').text()).toBe('Posėdis');
    expect(back.attributes('href')).toBe(route('meetings.show'));
  });
});
