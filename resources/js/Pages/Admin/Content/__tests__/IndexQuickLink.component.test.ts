import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';

import IndexQuickLink from '@/Pages/Admin/Content/IndexQuickLink.vue';
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

  const paramEntries = params ? Object.entries(params).filter(([, v]) => v !== undefined) : [];
  const queryString = paramEntries.length > 0
    ? `?${paramEntries.map(([k, v]) => `${k}=${encodeURIComponent(String(v))}`).join('&')}`
    : '';
  return `/mocked-route/${name}${queryString}`;
};

vi.stubGlobal('route', mockRoute);

const pageStubs = {
  ...commonStubs,
  DeleteConfirmationDialog: {
    template: '<div data-testid="delete-dialog" />',
  },
  ConfirmDangerousActionDialog: {
    props: ['open', 'confirmationText'],
    emits: ['confirm', 'update:open'],
    template: '<div v-if="open" data-testid="force-delete-dialog"><span>{{ confirmationText }}</span><button data-testid="confirm-force-delete" @click="$emit(\'confirm\')">confirm</button></div>',
  },
  IFluentLink24Regular: {
    template: '<span class="icon-link" />',
  },
  IFluentAdd24Regular: {
    template: '<span class="icon-add" />',
  },
  IFluentReOrderDotsVertical24Regular: {
    template: '<span class="icon-reorder" />',
  },
  IFluentEdit24Regular: {
    template: '<span class="icon-edit" />',
  },
  IFluentDelete24Regular: {
    template: '<span class="icon-delete" />',
  },
  IFluentSave24Regular: {
    template: '<span class="icon-save" />',
  },
  Icon: {
    props: ['icon'],
    template: '<span class="iconify" />',
  },
};

function createWrapper(props: Record<string, unknown>) {
  return mount(IndexQuickLink, {
    props: {
      quickLinks: [],
      tenant: null,
      tenants: [],
      currentLang: 'lt',
      ...props,
    },
    global: {
      stubs: {
        ...pageStubs,
      },
    },
  });
}

describe('IndexQuickLink.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: {
        can: {
          forceDelete: {
            quickLink: true,
          },
        },
      },
    }));
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  it('shows empty-state create button when there are no quick links', () => {
    wrapper = createWrapper({ quickLinks: [] });

    const emptyButton = wrapper.find('[data-testid="empty-create-button"]');
    expect(emptyButton.exists()).toBe(true);
    expect(emptyButton.attributes('href')).toContain('/mocked-route/quickLinks.create');
  });

  it('shows inline create button above the list when quick links exist', () => {
    wrapper = createWrapper({
      quickLinks: [
        { id: 1, text: 'Link 1', link: 'https://example.com/1', icon: null, order: 1 },
      ],
    });

    const button = wrapper.find('[data-testid="inline-create-button"]');
    expect(button.exists()).toBe(true);
    expect(button.attributes('href')).toContain('/mocked-route/quickLinks.create');
  });

  it('shows trash view controls for deleted quick links', async () => {
    wrapper = createWrapper({
      showDeleted: true,
      deletedCount: 1,
      quickLinks: [
        { id: 5, text: 'Deleted link', link: 'https://example.com/deleted', icon: null, order: 1 },
      ],
    });

    expect(wrapper.find('[data-testid="show-deleted-toggle"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('trash.showing_deleted_only_description');
    expect(wrapper.find('.handle').exists()).toBe(false);
    expect(wrapper.find('[data-testid="inline-create-button"]').exists()).toBe(false);

    await wrapper.find('[data-testid="restore-button"]').trigger('click');

    expect(router.patch).toHaveBeenCalledWith('/mocked-route/quickLinks.restore/5', {}, { preserveScroll: true });
  });

  it('gates and confirms permanent quick link deletion', async () => {
    wrapper = createWrapper({
      showDeleted: true,
      deletedCount: 1,
      quickLinks: [
        { id: 7, text: 'Danger link', link: 'https://example.com/danger', icon: null, order: 1 },
      ],
    });

    await wrapper.find('[data-testid="force-delete-button"]').trigger('click');

    expect(wrapper.find('[data-testid="force-delete-dialog"]').text()).toContain('Danger link');

    await wrapper.find('[data-testid="confirm-force-delete"]').trigger('click');

    expect(router.delete).toHaveBeenCalledWith('/mocked-route/quickLinks.forceDelete/7', { preserveScroll: true });
  });

  it('hides permanent delete when the user lacks force delete permission', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: {
        can: {
          forceDelete: {
            quickLink: false,
          },
        },
      },
    }));

    wrapper = createWrapper({
      showDeleted: true,
      deletedCount: 1,
      quickLinks: [
        { id: 8, text: 'Protected link', link: 'https://example.com/protected', icon: null, order: 1 },
      ],
    });

    expect(wrapper.find('[data-testid="force-delete-button"]').exists()).toBe(false);
  });
});
