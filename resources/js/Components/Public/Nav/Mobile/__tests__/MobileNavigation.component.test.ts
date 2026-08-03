import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h, inject, nextTick, provide, reactive, type InjectionKey } from 'vue';
import { usePage } from '@inertiajs/vue3';

import MobileNavigation from '../MobileNavigation.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

// The real Drawer is a vaul-vue portal — unreliable in jsdom, and it renders
// its content regardless of `open` (visibility is CSS/animation-driven, not
// v-if-gated), so the panel-stack navigation underneath is exercised the
// same way with or without a real open/close. The one thing worth wiring is
// the trigger -> `open` link itself, via provide/inject mirroring vaul's own
// context pattern, since one test needs a real true->false transition to
// exercise the "reset stack on navigate" watcher. The drawer's actual
// open/close animation is a manual-QA concern either way.
const openKey: InjectionKey<{ setOpen: (value: boolean) => void }> = Symbol('drawer-open');

const DrawerStub = defineComponent({
  name: 'DrawerStub',
  props: { open: { type: Boolean, default: false } },
  emits: ['update:open'],
  setup(_props, { emit, slots }) {
    provide(openKey, { setOpen: (value: boolean) => emit('update:open', value) });
    return () => h('div', slots.default?.());
  },
});

const DrawerTriggerStub = defineComponent({
  name: 'DrawerTriggerStub',
  setup(_props, { slots }) {
    const ctx = inject(openKey, null);
    return () => h('div', { 'data-testid': 'drawer-trigger', 'onClick': () => ctx?.setOpen(true) }, slots.default?.());
  },
});

const drawerStubs = {
  Drawer: DrawerStub,
  DrawerTrigger: DrawerTriggerStub,
  DrawerContent: { template: '<div><slot /></div>' },
  DrawerTitle: { template: '<h2><slot /></h2>' },
  DrawerDescription: { template: '<p><slot /></p>' },
  DrawerClose: { template: '<button type="button"><slot /></button>' },
};

const iconStubs = {
  LineHorizontal320Filled: { template: '<span class="icon-menu" />' },
  IFluentPerson24Filled: { template: '<span class="icon-person-filled" />' },
  IFluentPerson24Regular: { template: '<span class="icon-person-regular" />' },
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
  const mockPage = buildMockPage(overrides);
  vi.mocked(usePage).mockReturnValue(mockPage);

  return mount(MobileNavigation, {
    global: {
      stubs: { ...commonStubs, ...drawerStubs, ...iconStubs },
    },
  });
}

// The back button always stays in the DOM (so the title's indent doesn't
// jump between panels) — it's toggled via `invisible`/`aria-hidden`, not
// v-if, so "shown" means visible, not merely present.
function backButtonVisible(wrapper: ReturnType<typeof mount>) {
  const button = wrapper.find('[aria-label="navigation.back"]');
  return button.exists() && !button.classes().includes('invisible') && button.attributes('aria-hidden') !== 'true';
}

describe('MobileNavigation.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('shows the root panel with every section from mainNavigation', () => {
    const wrapper = mountNav();

    expect(wrapper.text()).toContain('VU SA');
    expect(wrapper.text()).toContain('Studijos ir mokslas');
    // The back button stays reserved in the header (no layout shift) but is hidden at the root.
    expect(backButtonVisible(wrapper)).toBe(false);
  });

  it('drills into a section, flattening every column into one list, with a back button', async () => {
    const wrapper = mountNav();

    const sectionButton = wrapper.findAll('button').find(b => b.text() === 'VU SA');
    await sectionButton!.trigger('click');
    await nextTick();

    expect(wrapper.text()).toContain('Struktūra');
    expect(wrapper.text()).toContain('Strategija');
    expect(wrapper.text()).toContain('Tvarumas');
    // Root-only content is gone.
    expect(wrapper.text()).not.toContain('Studijos ir mokslas');
    expect(backButtonVisible(wrapper)).toBe(true);
  });

  it('returns to the root panel via the back button', async () => {
    const wrapper = mountNav();

    await wrapper.findAll('button').find(b => b.text() === 'VU SA')!.trigger('click');
    await nextTick();
    await wrapper.find('[aria-label="navigation.back"]').trigger('click');
    await nextTick();

    expect(wrapper.text()).toContain('VU SA');
    expect(wrapper.text()).toContain('Studijos ir mokslas');
    expect(backButtonVisible(wrapper)).toBe(false);
  });

  it('resets the panel stack to root when the current path changes', async () => {
    const mockPage = reactive(buildMockPage());
    vi.mocked(usePage).mockReturnValue(mockPage as ReturnType<typeof usePage>);

    const wrapper = mount(MobileNavigation, {
      global: { stubs: { ...commonStubs, ...drawerStubs, ...iconStubs } },
    });

    // The stack only resets when the sheet transitions from open to closed —
    // open it via the trigger first so the later path change is a real flip.
    await wrapper.find('[data-testid="drawer-trigger"]').trigger('click');
    await wrapper.findAll('button').find(b => b.text() === 'VU SA')!.trigger('click');
    await nextTick();
    expect(backButtonVisible(wrapper)).toBe(true);

    mockPage.props.app.path = 'lt/kita-svetaine';
    await nextTick();

    expect(backButtonVisible(wrapper)).toBe(false);
    expect(wrapper.text()).toContain('Studijos ir mokslas');
  });

  it('shows a login row pointing at /login with the authenticated user name', () => {
    const wrapper = mountNav({
      auth: {
        user: { id: 1, name: 'Testas Testauskas' },
      },
    });

    const loginLink = wrapper.find('a[href="/login"]');
    expect(loginLink.exists()).toBe(true);
    expect(loginLink.text()).toContain('Testas Testauskas');
  });
});
