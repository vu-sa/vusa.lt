import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';

import IndexNavigation from '@/Pages/Admin/Navigation/IndexNavigation.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.mock('@vueuse/integrations/useSortable', () => ({
  useSortable: vi.fn(),
}));

const mockRoute = (name?: string, params?: Record<string, unknown> | string | number) => {
  if (name === undefined) {
    return {
      current: (pattern: string) => pattern === '*.index',
    };
  }

  if (typeof params === 'string' || typeof params === 'number') {
    return `/mocked-route/${name}/${params}`;
  }

  const paramEntries = params ? Object.entries(params).filter(([, value]) => value !== undefined) : [];
  const queryString = paramEntries.length > 0
    ? `?${paramEntries.map(([key, value]) => `${key}=${encodeURIComponent(String(value))}`).join('&')}`
    : '';

  return `/mocked-route/${name}${queryString}`;
};

vi.stubGlobal('route', mockRoute);

const pageStubs = {
  ...commonStubs,
  PageContent: {
    props: ['title'],
    template: '<section><slot /></section>',
  },
  ConfirmDangerousActionDialog: {
    props: ['open', 'confirmationText'],
    emits: ['confirm', 'update:open'],
    template: '<div v-if="open" data-testid="force-delete-dialog"><span>{{ confirmationText }}</span><button data-testid="confirm-force-delete" @click="$emit(\'confirm\')">confirm</button></div>',
  },
  NavigationBuilder: {
    props: ['roots', 'lang', 'translationSummary'],
    emits: ['update:lang'],
    template: '<div data-testid="navigation-builder">{{ lang }}<button data-testid="trigger-lang-change" @click="$emit(\'update:lang\', \'en\')">switch</button></div>',
  },
};

function createWrapper(props: Record<string, unknown>) {
  return mount(IndexNavigation, {
    props: {
      navigation: [],
      ...props,
    },
    global: {
      stubs: {
        ...pageStubs,
      },
    },
  });
}

describe('IndexNavigation.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: {
        can: {
          forceDelete: {
            navigation: true,
          },
        },
      },
    }));
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  it('renders a read-only trash list without sortable controls', () => {
    wrapper = createWrapper({
      showDeleted: true,
      deletedCount: 1,
      navigation: [
        { id: 11, name: 'Deleted navigation', url: '/deleted', parent_id: 0, order: 1, deleted_at: '2026-07-27T00:00:00Z' },
      ],
    });

    expect(wrapper.find('[data-testid="show-deleted-toggle"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('trash.showing_deleted_only_description');
    expect(wrapper.text()).toContain('Deleted navigation');
    expect(wrapper.find('.handle').exists()).toBe(false);
    expect(wrapper.find('[data-testid="main-navigation-menu-content"]').exists()).toBe(false);
  });

  it('restores deleted navigation records', async () => {
    wrapper = createWrapper({
      showDeleted: true,
      deletedCount: 1,
      navigation: [
        { id: 12, name: 'Deleted navigation', url: '/deleted', parent_id: 0, order: 1, deleted_at: '2026-07-27T00:00:00Z' },
      ],
    });

    await wrapper.find('[data-testid="restore-button"]').trigger('click');

    expect(router.patch).toHaveBeenCalledWith('/mocked-route/navigation.restore/12', {}, { preserveScroll: true });
  });

  it('gates and confirms permanent navigation deletion', async () => {
    wrapper = createWrapper({
      showDeleted: true,
      deletedCount: 1,
      navigation: [
        { id: 13, name: 'Danger navigation', url: '/danger', parent_id: 0, order: 1, deleted_at: '2026-07-27T00:00:00Z' },
      ],
    });

    await wrapper.find('[data-testid="force-delete-button"]').trigger('click');

    expect(wrapper.find('[data-testid="force-delete-dialog"]').text()).toContain('Danger navigation');

    await wrapper.find('[data-testid="confirm-force-delete"]').trigger('click');

    expect(router.delete).toHaveBeenCalledWith('/mocked-route/navigation.forceDelete/13', { preserveScroll: true });
  });

  it('hides permanent delete when the user lacks force delete permission', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: {
        can: {
          forceDelete: {
            navigation: false,
          },
        },
      },
    }));

    wrapper = createWrapper({
      showDeleted: true,
      deletedCount: 1,
      navigation: [
        { id: 14, name: 'Protected navigation', url: '/protected', parent_id: 0, order: 1, deleted_at: '2026-07-27T00:00:00Z' },
      ],
    });

    expect(wrapper.find('[data-testid="force-delete-button"]').exists()).toBe(false);
  });

  it('delegates the live tree to NavigationBuilder with the current language', () => {
    wrapper = createWrapper({
      lang: 'lt',
      navigation: [{ id: 1, name: 'Root', url: '#', parent_id: 0, order: 0, is_active: true, extra_attributes: {}, links: [[], [], []], cols: 0 }],
    });

    const builder = wrapper.find('[data-testid="navigation-builder"]');
    expect(builder.exists()).toBe(true);
    expect(builder.text()).toContain('lt');
  });

  it('revisits the index with the new language when NavigationBuilder emits update:lang', async () => {
    wrapper = createWrapper({ lang: 'lt', navigation: [] });

    await wrapper.find('[data-testid="trigger-lang-change"]').trigger('click');

    expect(router.get).toHaveBeenCalledWith(
      '/mocked-route/navigation.index',
      { lang: 'en' },
      expect.objectContaining({ preserveScroll: true, preserveState: false }),
    );
  });
});
