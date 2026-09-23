import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';

import AdminShell from '../AdminShell.vue';

import { createShellFocusProvider, type ShellFocusContext } from '@/Composables/useShellFocus';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
// `virtual:pwa-register` only exists inside a Vite build.
vi.mock('@/Composables/usePWA', () => ({ usePWA: () => ({ isPWA: false, setAppBadge: vi.fn() }) }));

beforeEach(() => {
  vi.stubGlobal('route', () => ({ current: () => 'pages.edit', params: {} }));
});

afterEach(() => {
  vi.unstubAllGlobals();
});

const stubs = {
  StagingBanner: true,
  ImpersonateBanner: true,
  SystemAnnouncement: true,
  MobileMenuPanel: true,
  SectionTabs: { template: '<nav data-testid="section-tabs" />' },
  ShellBreadcrumbs: { template: '<nav data-testid="breadcrumbs" />' },
  MobileBottomBar: { template: '<nav data-testid="bottom-bar" />' },
  ShellTopBar: { props: ['focused'], template: '<header data-testid="top-bar" :data-focused="focused" />' },
};

function mountShell() {
  let focus: ShellFocusContext | undefined;

  const Host = defineComponent({
    setup() {
      focus = createShellFocusProvider();
      return () => h(AdminShell, null, { default: () => h('p', 'Puslapis') });
    },
  });

  const wrapper = mount(Host, { global: { stubs } });

  return { wrapper, focus: () => focus! };
}

describe('AdminShell focus mode', () => {
  it('shows the navigation chrome when no form is open', () => {
    const { wrapper } = mountShell();

    expect(wrapper.find('[data-testid="section-tabs"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="breadcrumbs"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="bottom-bar"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="top-bar"]').attributes('data-focused')).toBe('false');
  });

  it('gives way to the form while one holds focus, and returns when it leaves', async () => {
    const { wrapper, focus } = mountShell();

    const release = focus().enter();
    await wrapper.vm.$nextTick();

    expect(wrapper.find('[data-testid="section-tabs"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="breadcrumbs"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="bottom-bar"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="top-bar"]').attributes('data-focused')).toBe('true');

    release();
    await wrapper.vm.$nextTick();

    expect(wrapper.find('[data-testid="section-tabs"]').exists()).toBe(true);
  });

  it('stays focused across a form-to-form visit, where the next form enters before the last leaves', async () => {
    const { wrapper, focus } = mountShell();

    const releaseFirst = focus().enter();
    const releaseSecond = focus().enter();
    releaseFirst();
    releaseFirst();
    await wrapper.vm.$nextTick();

    expect(focus().isFocused.value).toBe(true);

    releaseSecond();
    expect(focus().isFocused.value).toBe(false);
  });
});
