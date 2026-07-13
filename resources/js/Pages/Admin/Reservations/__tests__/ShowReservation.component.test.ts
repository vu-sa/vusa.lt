import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

import ShowReservation from '@/Pages/Admin/Reservations/ShowReservation.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.mock('@vueuse/core', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@vueuse/core')>();

  return {
    ...actual,
    useStorage: vi.fn((_, defaultValue) => ref(defaultValue)),
  };
});

const mockRoute = (name?: string, params?: Record<string, unknown>) => {
  if (name === undefined) {
    return { current: () => false };
  }

  const paramEntries = params ? Object.entries(params).filter(([, v]) => v !== undefined) : [];
  const queryString = paramEntries.length > 0
    ? `?${paramEntries.map(([k, v]) => `${k}=${encodeURIComponent(String(v))}`).join('&')}`
    : '';

  return `/mocked-route/${name}${queryString}`;
};

vi.stubGlobal('route', mockRoute);

const stubs = {
  ReservationHero: {
    name: 'ReservationHero',
    props: ['reservation'],
    template: '<div data-testid="reservation-hero" />',
  },
  DiscussionPanel: {
    name: 'DiscussionPanel',
    props: ['commentableType', 'commentableId'],
    template: '<div data-testid="discussion-panel" :data-commentable-type="commentableType" :data-commentable-id="commentableId" />',
  },
  ReservationResourceTable: {
    name: 'ReservationResourceTable',
    props: ['reservation', 'selectedReservationResource'],
    template: '<div data-testid="reservation-resource-table" />',
  },
  ReservationResourceForm: {
    template: '<div data-testid="reservation-resource-form" />',
  },
  UserAvatar: {
    props: ['user', 'size'],
    template: '<span class="user-avatar" />',
  },
  MdSuspenseWrapper: {
    template: '<div data-testid="md-suspense-wrapper" />',
  },
  IFluentTextDescription24Regular: {
    template: '<span class="icon-description" />',
  },
  IFluentAdd24Filled: {
    template: '<span class="icon-add" />',
  },
  IFluentCheckmark24Filled: {
    template: '<span class="icon-checkmark" />',
  },
  Dialog: {
    props: ['open'],
    template: '<div data-testid="dialog"><slot /></div>',
  },
  DialogContent: {
    template: '<div><slot /></div>',
  },
  DialogHeader: {
    template: '<div><slot /></div>',
  },
  DialogTitle: {
    template: '<div><slot /></div>',
  },
  MultiSelect: {
    props: ['modelValue', 'options'],
    template: '<div data-testid="multi-select" />',
  },
};

function createWrapper(props: Record<string, unknown> = {}) {
  return mount(ShowReservation, {
    props: {
      reservation: {
        id: 'res1',
        name: 'Test Reservation',
        description: 'A test reservation',
        start_time: new Date().toISOString(),
        end_time: new Date(Date.now() + 86400000).toISOString(),
        resources: [],
        users: [],
      },
      ...props,
    },
    global: { stubs },
  });
}

describe('ShowReservation.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders a reservation-bound DiscussionPanel on the page', () => {
    const wrapper = createWrapper();

    const panel = wrapper.find('[data-testid="discussion-panel"]');

    expect(panel.exists()).toBe(true);
    expect(panel.attributes('data-commentable-type')).toBe('reservation');
    expect(panel.attributes('data-commentable-id')).toBe('res1');
  });

  it('does not render a separate comments tab', () => {
    const wrapper = createWrapper();

    expect(wrapper.text()).not.toContain('Komentarai');
  });
});
