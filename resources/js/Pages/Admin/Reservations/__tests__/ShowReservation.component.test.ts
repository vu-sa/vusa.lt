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
  // reka-ui's Select relies on popper positioning, so drive the selection through a plain stub.
  Select: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    provide() {
      return {
        selectValue: (value: string) => (this as unknown as { $emit: (e: string, v: string) => void }).$emit('update:modelValue', value),
      };
    },
    template: '<div data-testid="tenant-filter"><slot /></div>',
  },
  SelectTrigger: { template: '<div><slot /></div>' },
  SelectValue: { props: ['placeholder'], template: '<span />' },
  SelectContent: { template: '<div><slot /></div>' },
  SelectItem: {
    props: ['value'],
    inject: ['selectValue'],
    template: '<button class="select-item" :data-value="value" @click="selectValue(value)"><slot /></button>',
  },
};

const resourceFromTenant = (id: string, tenantId: string, tenantShortname: string) => ({
  id,
  name: `Resource ${id}`,
  tenant: { id: tenantId, shortname: tenantShortname },
  pivot: { id: `pivot-${id}`, state: 'created', quantity: 1 },
});

const baseReservation = {
  id: 'res1',
  name: 'Test Reservation',
  description: 'A test reservation',
  start_time: new Date().toISOString(),
  end_time: new Date(Date.now() + 86400000).toISOString(),
  resources: [] as ReturnType<typeof resourceFromTenant>[],
  users: [],
};

function createWrapper(props: Record<string, unknown> = {}) {
  return mount(ShowReservation, {
    props: {
      reservation: baseReservation,
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

  it('hides the tenant filter when every resource belongs to the same tenant', () => {
    const wrapper = createWrapper({
      reservation: {
        ...baseReservation,
        resources: [
          resourceFromTenant('r1', 'tenant-1', 'VU SA MIF'),
          resourceFromTenant('r2', 'tenant-1', 'VU SA MIF'),
        ],
      },
    });

    expect(wrapper.find('[data-testid="tenant-filter"]').exists()).toBe(false);
  });

  it('filters the resource table down to the selected tenant', async () => {
    const wrapper = createWrapper({
      reservation: {
        ...baseReservation,
        resources: [
          resourceFromTenant('r1', 'tenant-1', 'VU SA MIF'),
          resourceFromTenant('r2', 'tenant-2', 'VU SA CHGF'),
          resourceFromTenant('r3', 'tenant-2', 'VU SA CHGF'),
        ],
      },
    });

    expect(wrapper.find('[data-testid="tenant-filter"]').exists()).toBe(true);

    const table = () => wrapper.findComponent({ name: 'ReservationResourceTable' });

    expect((table().props('reservation') as { resources: unknown[] }).resources).toHaveLength(3);

    await wrapper.find('[data-value="tenant-2"]').trigger('click');

    const filtered = (table().props('reservation') as { resources: { id: string }[] }).resources;

    expect(filtered.map(resource => resource.id)).toEqual(['r2', 'r3']);
  });
});
