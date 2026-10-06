import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { defineComponent, h } from 'vue';

import AdminShell from '../AdminShell.vue';

import { atstovavimas, pradzia } from './fixtures';

import { createShellFocusProvider, type ShellFocusContext } from '@/Composables/useShellFocus';
import { createMockPage } from '@/tests/helpers/createMockPage';

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
  StagingBanner: {
    props: ['dismissed', 'compact'],
    emits: ['update:dismissed'],
    template: '<button data-testid="staging" :data-compact="compact" :data-dismissed="dismissed" @click="$emit(\'update:dismissed\', !dismissed)" />',
  },
  ImpersonateBanner: true,
  SystemAnnouncement: true,
  MobileMenuPanel: true,
  SectionTabs: { template: '<nav data-testid="section-tabs" v-bind="$attrs" />' },
  MobileBottomBar: { template: '<nav data-testid="bottom-bar" />' },
  MobileContextBar: { template: '<div data-testid="context-bar"><slot name="staging-warning" /></div>' },
  ShellTopBar: { props: ['focused'], template: '<header data-testid="top-bar" :data-focused="focused"><slot name="staging-warning" /></header>' },
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
  it('keeps the navigation over the scrolling page while the mobile bar stays outside', () => {
    const { wrapper } = mountShell();
    const scrollArea = wrapper.find('[data-slot="admin-scroll-area"]');

    expect(scrollArea.classes()).toContain('overflow-auto');
    expect(scrollArea.attributes('scroll-region')).toBeDefined();
    expect(scrollArea.find('.sticky').find('[data-testid="top-bar"]').exists()).toBe(true);
    expect(scrollArea.find('[data-slot="admin-page-measure"]').text()).toBe('Puslapis');
    expect(scrollArea.find('[data-testid="bottom-bar"]').exists()).toBe(false);
  });

  it('shows the navigation chrome when no form is open', () => {
    const { wrapper } = mountShell();

    expect(wrapper.find('[data-testid="section-tabs"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="bottom-bar"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="top-bar"]').attributes('data-focused')).toBe('false');
  });

  it('gives way to the form while one holds focus, and returns when it leaves', async () => {
    const { wrapper, focus } = mountShell();

    const release = focus().enter();
    await wrapper.vm.$nextTick();

    expect(wrapper.find('[data-testid="section-tabs"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="context-bar"]').exists()).toBe(false);
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

describe('AdminShell on phones', () => {
  const onRoute = (routeName: string) => {
    vi.stubGlobal('route', () => ({ current: () => routeName, params: {} }));
    vi.mocked(usePage).mockReturnValue(createMockPage({
      adminNavigation: { workspaces: [pradzia, atstovavimas] },
    }) as ReturnType<typeof usePage>);
  };

  it.each(['dashboard', 'meetings.index'])('swaps the tab row for the context bar below md on %s', (routeName) => {
    onRoute(routeName);
    const { wrapper } = mountShell();

    expect(wrapper.find('[data-testid="section-tabs"]').classes()).toContain('max-md:hidden');
    expect(wrapper.find('.sticky').find('[data-testid="context-bar"]').exists()).toBe(true);
  });
});

describe('AdminShell staging warning', () => {
  it('keeps the collapsed state across shell mounts and lets the topbar reopen it', async () => {
    localStorage.clear();
    const first = mountShell();
    await first.wrapper.findAll('[data-testid="staging"]')[0].trigger('click');

    expect(localStorage.getItem('admin-staging-warning:1')).toBe('true');
    first.wrapper.unmount();

    const second = mountShell();
    const topbarWarning = second.wrapper.find('[data-testid="top-bar"] [data-testid="staging"]');
    expect(topbarWarning.attributes('data-dismissed')).toBe('true');
    await topbarWarning.trigger('click');

    expect(localStorage.getItem('admin-staging-warning:1')).toBe('false');
    second.wrapper.unmount();
    localStorage.clear();
  });
});
