import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import MobileNavRootPanel from '../MobileNavRootPanel.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mainNavigation = [
  { id: '1', name: 'VU SA', cols: 1, links: [[{ name: 'Struktūra', url: '/struktura' }]] },
  { id: '2', name: 'Studijos ir mokslas', cols: 1, links: [[{ name: 'Stipendijos', url: '/stipendijos' }]] },
];

const tenantLinks = [
  { id: 1, text: 'Tapk nariu', link: '/nariu-anketa', icon: 'people-24-regular', is_important: true },
  { id: 2, text: 'Renginių kalendorius', link: '/kalendorius', icon: 'calendar-24-regular', is_important: false },
];

function mountPanel(overrides: Record<string, unknown> = {}) {
  vi.mocked(usePage).mockReturnValue(createMockPage({
    mainNavigation,
    tenant: { alias: 'mif', shortname: 'VU MIF', links: tenantLinks },
    app: { path: 'lt' },
    tenants: [
      { id: 1, alias: 'vusa', fullname: 'VU studentų atstovybė', type: 'pagrindinis' },
      { id: 2, alias: 'mif', fullname: 'VU MIF studentų atstovybė VU MIF', type: 'padalinys' },
    ],
    ...overrides,
  }));

  return mount(MobileNavRootPanel, {
    global: {
      stubs: {
        Icon: { props: ['icon'], template: '<span class="iconify" />' },
      },
    },
  });
}

describe('MobileNavRootPanel.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('lists every root section from mainNavigation', () => {
    const wrapper = mountPanel();

    expect(wrapper.text()).toContain('VU SA');
    expect(wrapper.text()).toContain('Studijos ir mokslas');
  });

  /**
   * The panel is an accordion, not a drill-down: sections expand in place so the rest of the menu
   * stays on screen. That is what replaced the old back-button stack.
   */
  it('expands a section in place, keeping the other sections visible', async () => {
    const wrapper = mountPanel();

    expect(wrapper.text()).not.toContain('Struktūra');

    await wrapper.findAll('nav button')[1]!.trigger('click');

    expect(wrapper.text()).toContain('Struktūra');
    expect(wrapper.text()).toContain('Studijos ir mokslas');
  });

  it('collapses an open section when its trigger is clicked again', async () => {
    const wrapper = mountPanel();
    const trigger = wrapper.findAll('nav button')[1]!;

    await trigger.trigger('click');
    expect(wrapper.text()).toContain('Struktūra');

    await trigger.trigger('click');
    expect(wrapper.text()).not.toContain('Struktūra');
  });

  it('keeps only one section open at a time', async () => {
    const wrapper = mountPanel();
    const triggers = wrapper.findAll('nav button');

    await triggers[1]!.trigger('click');
    await triggers[2]!.trigger('click');

    expect(wrapper.text()).toContain('Stipendijos');
    expect(wrapper.text()).not.toContain('Struktūra');
  });

  it('renders the tenant quick links', () => {
    const wrapper = mountPanel();

    expect(wrapper.text()).toContain('Tapk nariu');
    expect(wrapper.text()).toContain('Renginių kalendorius');
  });

  it('offers the tenants as their own section when switching is allowed', async () => {
    const wrapper = mountPanel();

    // The tenants section leads the accordion, so it is the first trigger.
    await wrapper.findAll('nav button')[0]!.trigger('click');

    expect(wrapper.text()).toContain('VU MIF');
  });

  it('hides the tenant section when switching is not allowed on this page', () => {
    const wrapper = mountPanel({ app: { path: 'lt/kazkas' } });

    // Only the two `mainNavigation` sections remain.
    expect(wrapper.findAll('nav button')).toHaveLength(2);
  });
});
