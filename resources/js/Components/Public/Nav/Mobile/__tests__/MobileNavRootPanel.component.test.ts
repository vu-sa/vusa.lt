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

  it('emits openSection with the clicked section index', async () => {
    const wrapper = mountPanel();

    const buttons = wrapper.findAll('button').filter(b => b.text().includes('Studijos ir mokslas'));
    await buttons[0].trigger('click');

    expect(wrapper.emitted('openSection')).toEqual([[1]]);
  });

  it('renders the tenant quick links', () => {
    const wrapper = mountPanel();

    expect(wrapper.text()).toContain('Tapk nariu');
    expect(wrapper.text()).toContain('Renginių kalendorius');
  });

  it('shows the tenant switcher row and emits openTenants when switching is allowed', async () => {
    const wrapper = mountPanel({ app: { path: 'lt' } });

    const tenantRow = wrapper.findAll('button').find(b => b.text().includes('MIF'));
    expect(tenantRow?.exists()).toBe(true);

    await tenantRow!.trigger('click');
    expect(wrapper.emitted('openTenants')).toHaveLength(1);
  });

  it('hides the tenant switcher row when switching is not allowed on this page', () => {
    const wrapper = mountPanel({ app: { path: 'lt/kazkas/kitas' } });

    const tenantRow = wrapper.findAll('button').find(b => b.text().includes('MIF'));
    expect(tenantRow).toBeUndefined();
  });
});
