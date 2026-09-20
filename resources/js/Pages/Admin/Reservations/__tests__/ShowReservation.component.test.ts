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
  RecordPage: {
    props: ['title', 'sections', 'facts', 'primaryAction', 'overflowActions'],
    emits: ['action'],
    template: `
      <div>
        <h1>{{ title }}</h1>
        <div data-testid="tabs">{{ sections.map(s => s.value + ':' + (s.count ?? '')).join('|') }}</div>
        <div data-testid="facts">{{ facts.map(f => f.key).join('|') }}</div>
        <button v-if="primaryAction" data-testid="primary" @click="$emit('action', primaryAction.key)">{{ primaryAction.label }}</button>
        <button v-for="a in overflowActions" :key="a.key" :data-testid="'overflow-' + a.key" @click="$emit('action', a.key)">{{ a.label }}</button>
        <slot name="subtitle" />
        <slot name="resources" />
        <slot name="description" />
        <slot name="activity" />
      </div>
    `,
  },
  RecordActivity: {
    props: ['subjectType', 'subjectId', 'commentableType', 'commentableId'],
    template: '<div data-testid="record-activity" :data-commentable-type="commentableType" :data-commentable-id="commentableId" />',
  },
  UsersAvatarGroup: {
    props: ['users', 'max', 'size'],
    template: '<div data-testid="users-avatar-group" />',
  },
  ReservationStateSummary: {
    props: ['states', 'unresolved'],
    template: '<div data-testid="state-summary" />',
  },
  ReservationResourceList: {
    name: 'ReservationResourceList',
    props: ['resources', 'target', 'canEdit'],
    template: '<div data-testid="resource-list" />',
  },
  ReservationDecisionDialog: {
    props: ['open', 'decision', 'targets'],
    template: '<div data-testid="decision-dialog" :data-open="open" />',
  },
  ConfirmDialog: true,
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

const resourceFromTenant = (id: string, tenantId: string, tenantShortname: string, state = 'created') => ({
  id,
  name: `Resource ${id}`,
  tenant: { id: tenantId, shortname: tenantShortname },
  pivot: { id: `pivot-${id}`, state, quantity: 1, start_time: '2026-09-20T10:00:00Z', end_time: '2026-09-21T10:00:00Z' },
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

/** The server's flagged copy of the reservation, one entry per item. */
const targetFor = (resources: ReturnType<typeof resourceFromTenant>[], approvable = true) => ({
  ...baseReservation,
  created_at: baseReservation.start_time,
  resources: resources.map(resource => ({
    ...resource,
    pivot: { ...resource.pivot, approvable, backtrackable: false, cancellable: false },
  })),
});

function createWrapper(props: Record<string, unknown> = {}, resources: ReturnType<typeof resourceFromTenant>[] = []) {
  return mount(ShowReservation, {
    props: {
      reservation: { ...baseReservation, resources },
      decisionTarget: targetFor(resources),
      can: { update: true, delete: true },
      ...props,
    },
    global: { stubs },
  });
}

const visibleIds = (wrapper: ReturnType<typeof mount>) =>
  (wrapper.findComponent({ name: 'ReservationResourceList' }).props('resources') as { id: string }[]).map(resource => resource.id);

describe('ShowReservation.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('closes with the reservation-bound activity feed, not a separate comments tab', () => {
    const wrapper = createWrapper();
    const activity = wrapper.find('[data-testid="record-activity"]');

    expect(activity.attributes('data-commentable-type')).toBe('reservation');
    expect(activity.attributes('data-commentable-id')).toBe('res1');
    expect(wrapper.find('[data-testid="tabs"]').text()).not.toContain('Komentarai');
  });

  it('renders the name, the period and the resource count as the title band', () => {
    const wrapper = createWrapper({}, [resourceFromTenant('r1', 'tenant-1', 'VU SA MIF')]);

    expect(wrapper.text()).toContain('Test Reservation');
    expect(wrapper.find('[data-testid="facts"]').text()).toContain('period');
    expect(wrapper.find('[data-testid="tabs"]').text()).toBe('resources:1|description:');
  });

  it('shows the state summary in the title band', () => {
    expect(createWrapper().find('[data-testid="state-summary"]').exists()).toBe(true);
  });

  it('hides the tenant filter when every resource belongs to the same tenant', () => {
    const wrapper = createWrapper({}, [
      resourceFromTenant('r1', 'tenant-1', 'VU SA MIF'),
      resourceFromTenant('r2', 'tenant-1', 'VU SA MIF'),
    ]);

    expect(wrapper.find('[data-testid="tenant-filter"]').exists()).toBe(false);
  });

  it('filters the resource list down to the selected tenant', async () => {
    const wrapper = createWrapper({}, [
      resourceFromTenant('r1', 'tenant-1', 'VU SA MIF'),
      resourceFromTenant('r2', 'tenant-2', 'VU SA CHGF'),
      resourceFromTenant('r3', 'tenant-2', 'VU SA CHGF'),
    ]);

    expect(wrapper.find('[data-testid="tenant-filter"]').exists()).toBe(true);
    expect(visibleIds(wrapper)).toEqual(['r1', 'r2', 'r3']);

    await wrapper.find('[data-value="tenant-2"]').trigger('click');

    expect(visibleIds(wrapper)).toEqual(['r2', 'r3']);
  });

  it('feeds the list the flagged decision target, not the record payload', () => {
    const wrapper = createWrapper({}, [resourceFromTenant('r1', 'tenant-1', 'VU SA MIF')]);
    const target = wrapper.findComponent({ name: 'ReservationResourceList' }).props('target') as { resources: { pivot: { approvable: boolean } }[] };

    expect(target.resources[0].pivot.approvable).toBe(true);
  });

  describe('actions', () => {
    it('offers adding a resource as the one primary action to someone who may update', () => {
      const wrapper = createWrapper({}, [resourceFromTenant('r1', 'tenant-1', 'VU SA MIF')]);

      expect(wrapper.find('[data-testid="primary"]').text()).toBe('Pridėti išteklių');
      expect(wrapper.find('[data-testid="overflow-add-user"]').exists()).toBe(true);
    });

    it('makes approving everything the primary action once several items wait on this user', async () => {
      const wrapper = createWrapper({}, [
        resourceFromTenant('r1', 'tenant-1', 'VU SA MIF'),
        resourceFromTenant('r2', 'tenant-1', 'VU SA MIF'),
      ]);

      expect(wrapper.find('[data-testid="primary"]').text()).toBe('reservations.actions.approve');
      expect(wrapper.find('[data-testid="decision-dialog"]').attributes('data-open')).toBe('false');

      await wrapper.find('[data-testid="primary"]').trigger('click');

      expect(wrapper.find('[data-testid="decision-dialog"]').attributes('data-open')).toBe('true');
    });

    it('offers no editing actions to someone who may only view', () => {
      const wrapper = createWrapper({ can: { update: false, delete: false } }, [
        resourceFromTenant('r1', 'tenant-1', 'VU SA MIF', 'reserved'),
      ]);

      expect(wrapper.find('[data-testid="primary"]').exists()).toBe(false);
      expect(wrapper.find('[data-testid="overflow-add-user"]').exists()).toBe(false);
      expect(wrapper.find('[data-testid="overflow-delete"]').exists()).toBe(false);
    });

    it('keeps deleting the reservation in the overflow, for those who may', () => {
      expect(createWrapper().find('[data-testid="overflow-delete"]').exists()).toBe(true);
    });
  });
});
