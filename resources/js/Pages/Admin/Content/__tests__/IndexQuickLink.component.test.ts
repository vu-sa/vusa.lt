import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import IndexQuickLink from '@/Pages/Admin/Content/IndexQuickLink.vue';

vi.mock('@vueuse/integrations/useSortable', () => ({
  useSortable: vi.fn(),
}));

const mockRoute = (name?: string, params?: any) => {
  if (name === undefined) {
    return {
      current: (pattern: string) => pattern === '*.index',
    };
  }
  const paramEntries = params ? Object.entries(params).filter(([, v]) => v !== undefined) : [];
  const queryString = paramEntries.length > 0
    ? `?${paramEntries.map(([k, v]) => `${k}=${encodeURIComponent(String(v))}`).join('&')}`
    : '';
  return `/mocked-route/${name}${queryString}`;
};

vi.stubGlobal('route', mockRoute);

const commonStubs = {
  DeleteConfirmationDialog: {
    template: '<div data-testid="delete-dialog" />',
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
        ...commonStubs,
      },
    },
  });
}

describe('IndexQuickLink.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    vi.clearAllMocks();
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
});
