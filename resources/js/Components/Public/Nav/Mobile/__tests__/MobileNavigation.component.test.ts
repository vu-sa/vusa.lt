import { describe, it, expect, vi, beforeEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { reactive } from 'vue';
import { usePage } from '@inertiajs/vue3';

import MobileNavigation from '../MobileNavigation.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { ssrRoundTrip } from '@/tests/helpers/ssrRoundTrip';
import { commonStubs } from '@/tests/stubs';
import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

/**
 * The mobile menu is a full-viewport panel teleported to `body`, opened by the header button —
 * not a sheet with a drill-down stack. `attachTo` is required so the teleport target exists and
 * `wrapper.html()` can see the panel.
 */
const iconStubs = {
  LineHorizontal320Filled: { template: '<span class="icon-menu" />' },
  IFluentGrid24Filled: { template: '<span data-slot="mano-vusa-button-icon" class="icon-grid-filled" />' },
  IFluentGrid24Regular: { template: '<span data-slot="mano-vusa-button-icon" class="icon-grid" />' },
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
      stubs: {
        ...commonStubs,
        ...iconStubs,
        DropdownMenu: false,
        DropdownMenuTrigger: false,
        DropdownMenuContent: false,
        DropdownMenuItem: false,
        DialogContent: false,
        DialogTitle: false,
      },
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

  it('opens navigation as a modal dialog', async () => {
    const wrapper = await openNav();

    expect(document.querySelector('[data-slot="mobile-navigation"]')?.getAttribute('aria-modal')).toBe('true');
    await wrapper.vm.$nextTick();
    expect(document.body.style.overflow).toBe('hidden');

    wrapper.unmount();
    await wrapper.vm.$nextTick();
    expect(document.body.style.overflow).toBe('');
  });

  it('closes on the desktop breakpoint except when XL text keeps mobile navigation visible', async () => {
    const query = Object.assign(new EventTarget(), {
      matches: false,
      media: '(min-width: 64rem)',
      onchange: null,
      addListener: vi.fn(),
      removeListener: vi.fn(),
    });
    const original = window.matchMedia;
    window.matchMedia = vi.fn(() => query);
    const preferences = useAccessibilityPreferences();
    preferences.fontScale.value = 'xl';
    const wrapper = await openNav();

    try {
      Object.assign(query, { matches: true });
      query.dispatchEvent(Object.assign(new Event('change'), { matches: true }));
      await wrapper.vm.$nextTick();
      expect(document.querySelector('[data-slot="mobile-navigation"]')).not.toBeNull();

      preferences.fontScale.value = 'm';
      await wrapper.vm.$nextTick();
      expect(document.querySelector('[data-slot="mobile-navigation"]')).toBeNull();
      await flushPromises();
      expect(document.body.style.pointerEvents).not.toBe('none');
    }
    finally {
      wrapper.unmount();
      preferences.reset();
      window.matchMedia = original;
    }
  });

  it('closes when the current path changes', async () => {
    // The component captures `usePage()` once at setup, so navigation has to be simulated by
    // mutating that same reactive object — swapping the mock's return value would not reach it.
    const page = reactive(buildMockPage());
    vi.mocked(usePage).mockReturnValue(page);

    const wrapper = mount(MobileNavigation, {
      attachTo: document.body,
      global: { stubs: { ...commonStubs, ...iconStubs, DialogContent: false, DialogTitle: false } },
    });

    await wrapper.find('button').trigger('click');
    expect(document.querySelector('[role="dialog"]')).not.toBeNull();

    page.props.app.path = 'lt/naujienos';
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    expect(document.querySelector('[role="dialog"]')).toBeNull();

    wrapper.unmount();
  });

  it('closes when the Escape key is pressed', async () => {
    const wrapper = await openNav();
    expect(document.querySelector('[role="dialog"]')).not.toBeNull();

    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
    await wrapper.vm.$nextTick();

    expect(document.querySelector('[role="dialog"]')).toBeNull();

    wrapper.unmount();
  });

  it.each([
    ['[data-slot="accessibility-menu-trigger"]', '[data-slot="accessibility-menu"]'],
    ['[data-slot="locale-button"]', '[data-slot="locale-menu"]'],
  ])('keeps navigation open when Escape dismisses %s', async (triggerSelector, popupSelector) => {
    const wrapper = await openNav();
    (document.querySelector(triggerSelector) as HTMLElement).click();
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();
    expect(document.querySelector(popupSelector)).not.toBeNull();

    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    await wrapper.vm.$nextTick();
    await wrapper.vm.$nextTick();

    expect(document.querySelector(popupSelector)?.getAttribute('data-state')).toBe('closed');
    expect(document.querySelector('[data-slot="mobile-navigation"]')?.getAttribute('data-state')).toBe('open');

    wrapper.unmount();
  });

  it('shows the authenticated account row as a public outline button', async () => {
    const wrapper = await openNav({ auth: { user: { id: 1, name: 'Testas Testauskas' }, can: {} } });

    const accountLink = document.querySelector('a[title="Testas Testauskas"]') as HTMLElement;
    expect(accountLink).not.toBeNull();
    expect(accountLink.getAttribute('href')).toContain('dashboard');
    expect(accountLink.textContent).toContain('Mano VU SA');
    expect(accountLink.className).toContain('border');
    expect(accountLink.className).toContain('text-foreground/70');
    expect(accountLink.className).toContain('hover:text-brand');
    const icon = document.querySelector('[data-slot="mano-vusa-button-icon"]') as HTMLElement;
    expect(icon).not.toBeNull();
    expect(icon.getAttribute('class')).toContain('text-brand');

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

  it('hydrates server markup without touching <body>, since SSR drops the teleported panel', async () => {
    vi.mocked(usePage).mockReturnValue(buildMockPage());
    const pageData = document.createElement('script');
    document.body.prepend(pageData);

    const { hydrationWarnings, unmount } = await ssrRoundTrip(MobileNavigation);

    expect(hydrationWarnings).toEqual([]);
    expect(pageData.isConnected).toBe(true);
    unmount();
    pageData.remove();
  });
});
