import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { reactive } from 'vue';
import { usePage } from '@inertiajs/vue3';

import MobileNavigation from '../MobileNavigation.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

/**
 * The mobile menu is a full-viewport panel teleported to `body`, opened by the header button —
 * not a sheet with a drill-down stack. `attachTo` is required so the teleport target exists and
 * `wrapper.html()` can see the panel.
 */
const iconStubs = {
  LineHorizontal320Filled: { template: '<span class="icon-menu" />' },
  IFluentPerson24Filled: { template: '<span class="icon-person-filled" />' },
  IFluentPerson24Regular: { template: '<span class="icon-person-regular" />' },
  IFluentDismiss24Regular: { template: '<span class="icon-close" />' },
  IFluentAdd24Regular: { template: '<span class="icon-add" />' },
  IFluentLocation24Regular: { template: '<span class="icon-location" />' },
  IFluentSearch20Filled: { template: '<span class="icon-search" />' },
  Icon: { props: ['icon'], template: '<span class="iconify" />' },
};

const mainNavigation = [
  {
    id: '1',
    name: 'VU SA',
    cols: 2,
    links: [
      [{ name: 'Struktūra', url: '/struktura' }],
      [{ name: 'Strategija', url: '/strategija' }, { name: 'Tvarumas', url: '/tvarumas' }],
    ],
  },
  { id: '2', name: 'Studijos ir mokslas', cols: 1, links: [[{ name: 'Stipendijos', url: '/stipendijos' }]] },
];

function buildMockPage(overrides: Record<string, unknown> = {}) {
  return createMockPage({
    mainNavigation,
    tenant: { alias: 'mif', shortname: 'VU MIF', links: [] },
    app: { path: 'lt', locale: 'lt' },
    tenants: [{ id: 1, alias: 'vusa', fullname: 'VU studentų atstovybė', type: 'pagrindinis' }],
    ...overrides,
  });
}

function mountNav(overrides: Record<string, unknown> = {}) {
  vi.mocked(usePage).mockReturnValue(buildMockPage(overrides));

  return mount(MobileNavigation, {
    attachTo: document.body,
    global: {
      stubs: { ...commonStubs, ...iconStubs },
    },
  });
}

/** The panel only exists once opened, so every content assertion goes through this. */
async function openNav(overrides: Record<string, unknown> = {}) {
  const wrapper = mountNav(overrides);
  await wrapper.find('button').trigger('click');

  return wrapper;
}

describe('MobileNavigation.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    document.body.style.overflow = '';
  });

  it('stays closed until the header button is pressed', () => {
    const wrapper = mountNav();

    expect(wrapper.html()).not.toContain('Studijos ir mokslas');
  });

  it('opens a full-viewport panel listing every section', async () => {
    const wrapper = await openNav();

    const panel = document.querySelector('[role="dialog"]');
    expect(panel).not.toBeNull();
    expect(panel!.className).toContain('fixed');
    expect(panel!.textContent).toContain('VU SA');
    expect(panel!.textContent).toContain('Studijos ir mokslas');

    wrapper.unmount();
  });

  it('expands a section in place rather than replacing the list', async () => {
    const wrapper = await openNav();

    const triggers = [...document.querySelectorAll('nav button')] as HTMLElement[];
    triggers.at(-2)!.click();
    await wrapper.vm.$nextTick();

    const panel = document.querySelector('[role="dialog"]')!;
    expect(panel.textContent).toContain('Struktūra');
    // The section it drilled into used to disappear; now the whole menu stays put.
    expect(panel.textContent).toContain('Studijos ir mokslas');

    wrapper.unmount();
  });

  it('locks the page behind the panel while it is open', async () => {
    const wrapper = await openNav();

    expect(document.body.style.overflow).toBe('hidden');

    wrapper.unmount();
  });

  it('closes when the current path changes', async () => {
    // The component captures `usePage()` once at setup, so navigation has to be simulated by
    // mutating that same reactive object — swapping the mock's return value would not reach it.
    const page = reactive(buildMockPage());
    vi.mocked(usePage).mockReturnValue(page);

    const wrapper = mount(MobileNavigation, {
      attachTo: document.body,
      global: { stubs: { ...commonStubs, ...iconStubs } },
    });

    await wrapper.find('button').trigger('click');
    expect(document.querySelector('[role="dialog"]')).not.toBeNull();

    page.props.app.path = 'lt/naujienos';
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    expect(document.querySelector('[role="dialog"]')).toBeNull();

    wrapper.unmount();
  });

  it('shows a login row pointing at /login with the authenticated user name', async () => {
    const wrapper = await openNav({ auth: { user: { id: 1, name: 'Testas Testauskas' }, can: {} } });

    const loginLink = document.querySelector('a[href="/login"]');
    expect(loginLink).not.toBeNull();
    expect(loginLink!.textContent).toContain('Testas Testauskas');

    wrapper.unmount();
  });
});
