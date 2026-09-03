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

  it('shows the authenticated account row with the second-menu link treatment', async () => {
    const wrapper = await openNav({ auth: { user: { id: 1, name: 'Testas Testauskas' }, can: {} } });

    const accountLink = document.querySelector('a[href="http://localhost:8000/mock/dashboard"]') as HTMLElement;
    expect(accountLink).not.toBeNull();
    expect(accountLink.textContent).toContain('Mano VU SA');
    expect(accountLink.className).toContain('text-muted-foreground');
    expect(accountLink.className).toContain('hover:text-brand');

    wrapper.unmount();
  });

  it('keeps the footer controls visually consistent and opens accessibility preferences', async () => {
    const wrapper = await openNav();
    const localeTrigger = document.querySelector('[data-slot="locale-button"]') as HTMLElement;
    const accessibilityTrigger = document.querySelector('[data-slot="accessibility-menu-trigger"]') as HTMLElement;

    expect(accessibilityTrigger.className).toContain('hover:border-brand');
    expect(accessibilityTrigger.className).toContain('dark:hover:text-brand');

    const searchButton = [...document.querySelectorAll('a')]
      .find(link => link.textContent?.includes('Paieška')) as HTMLElement;
    expect(searchButton.className).toContain('h-9');

    localeTrigger.click();
    await wrapper.vm.$nextTick();

    const localeMenu = document.querySelector('[data-slot="locale-menu"]') as HTMLElement;
    expect(localeMenu).not.toBeNull();
    expect(localeMenu.className).toContain('z-[70]');

    accessibilityTrigger.click();
    await wrapper.vm.$nextTick();

    const accessibilityMenu = document.querySelector('[data-slot="accessibility-menu"]') as HTMLElement;
    expect(accessibilityMenu).not.toBeNull();
    expect(accessibilityMenu.className).toContain('z-[70]');

    wrapper.unmount();
  });
});
