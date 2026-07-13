import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

import ShowReservations from '@/Pages/Admin/Dashboard/ShowReservations.vue';
import { commonStubs } from '@/tests/stubs';
import type { DashboardReservation, ReservationPivot, ReservationResourceState } from '@/Utils/ReservationStatus';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

/**
 * The active tab is persisted with useStorage. Driving it through the mock both selects the tab
 * under test and covers the restore path — reka-ui's Tabs do not activate from a synthetic click
 * in jsdom, and their switching behaviour is the library's to test, not ours.
 */
const storage = vi.hoisted(() => ({ tab: 'mine' }));

vi.mock('@vueuse/core', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@vueuse/core')>();

  return {
    ...actual,
    useStorage: vi.fn((key: string, defaultValue: unknown) =>
      ref(key === 'reservations-dashboard-tab' ? storage.tab : defaultValue)),
  };
});

vi.stubGlobal('route', (name?: string) => {
  if (name === undefined) {
    return { current: () => false };
  }

  return `/mocked/${name}`;
});

function pivot(id: string, state: ReservationResourceState, overrides: Partial<ReservationPivot> = {}): ReservationPivot {
  return {
    id,
    start_time: '2026-07-10T10:00:00Z',
    end_time: '2026-07-20T10:00:00Z',
    returned_at: null,
    updated_at: '2026-07-01T10:00:00Z',
    quantity: 1,
    state,
    state_properties: { tagType: 'info', description: '' },
    approvable: true,
    cancellable: false,
    ...overrides,
  };
}

function reservation(id: string, name: string, pivots: ReservationPivot[]): DashboardReservation {
  return {
    id,
    name,
    description: null,
    start_time: '2026-07-10T10:00:00Z',
    end_time: '2026-07-20T10:00:00Z',
    created_at: '2026-07-01T10:00:00Z',
    users: [{ id: 1, name: 'Gabija P.' }] as unknown as App.Entities.User[],
    resources: pivots.map(p => ({
      id: `resource-${p.id}`,
      name: `Resource ${p.id}`,
      tenant: { id: 'tenant-1', shortname: 'VU SA MIF' },
      pivot: p,
    })),
  };
}

const administered = [
  reservation('res-pending', 'Debate club finals', [pivot('p-1', 'created')]),
  // Lent and past its end time — the overdue row.
  reservation('res-overdue', 'Open lecture: AI', [pivot('p-2', 'lent', { end_time: '2020-01-01T10:00:00Z' })]),
  reservation('res-returned', 'Spring fair', [pivot('p-3', 'returned')]),
];

function createWrapper(props: Partial<Record<string, unknown>> = {}) {
  return mount(ShowReservations, {
    props: {
      myReservations: [reservation('res-mine', 'My workshop', [pivot('p-9', 'created', { cancellable: true })])],
      administeredReservations: administered,
      managedTenants: [{ id: 'tenant-1', shortname: 'VU SA MIF' }],
      ...props,
    },
    global: { stubs: { ...commonStubs } },
  });
}

const administeredTab = (target: ReturnType<typeof mount>) =>
  target.findAll('[role="tab"]')
    .find(tab => tab.text().includes('reservations.dashboard.tabs.administered'));

/** Mount with the administered tab already active. */
const mountAdministered = () => {
  storage.tab = 'administered';
  const target = createWrapper();
  storage.tab = 'mine';

  return target;
};

let wrapper: ReturnType<typeof mount>;

beforeEach(() => {
  storage.tab = 'mine';
  wrapper = createWrapper();
});

/** The number shown on a KPI tile. */
const kpiCount = (target: ReturnType<typeof mount>, key: string) => {
  const tile = target.findAll('button').find(button => button.text().includes(key));

  return Number(tile!.find('.tabular-nums').text());
};

describe('KPI strip', () => {
  it('renders each bucket', () => {
    const text = wrapper.text();

    expect(text).toContain('reservations.dashboard.kpi.awaiting');
    expect(text).toContain('reservations.dashboard.kpi.lent');
    expect(text).toContain('reservations.dashboard.kpi.overdue');
    expect(text).toContain('reservations.dashboard.kpi.returned');
  });

  it('counts reservations, so a tile agrees with the rows it filters to', async () => {
    // The tiles used to count resource items, which made "Overdue 10" filter down to 2 rows.
    const administeredView = mountAdministered();

    expect(kpiCount(administeredView, 'reservations.dashboard.kpi.overdue')).toBe(1);

    const overdueTile = administeredView.findAll('button')
      .find(button => button.text().includes('reservations.dashboard.kpi.overdue'));
    await overdueTile!.trigger('click');

    expect(administeredView.findAll('table tbody tr')).toHaveLength(1);
  });

  it('recounts as the search narrows the table', async () => {
    const administeredView = mountAdministered();

    expect(kpiCount(administeredView, 'reservations.dashboard.kpi.awaiting')).toBe(1);

    // A search that excludes the pending reservation must drop the awaiting count with it.
    await administeredView.find('input').setValue('Open lecture');

    expect(kpiCount(administeredView, 'reservations.dashboard.kpi.awaiting')).toBe(0);
    expect(kpiCount(administeredView, 'reservations.dashboard.kpi.overdue')).toBe(1);
  });

  it('filters the table to the bucket that was clicked', async () => {
    const administeredView = mountAdministered();

    // Both rows are visible before filtering.
    expect(administeredView.text()).toContain('Open lecture: AI');
    expect(administeredView.text()).toContain('Debate club finals');

    const overdueTile = administeredView.findAll('button')
      .find(button => button.text().includes('reservations.dashboard.kpi.overdue'));

    await overdueTile!.trigger('click');

    expect(overdueTile!.attributes('aria-pressed')).toBe('true');
    // Only the lent-and-late row survives; the pending one is filtered out.
    expect(administeredView.text()).toContain('Open lecture: AI');
    expect(administeredView.text()).not.toContain('Debate club finals');
  });

  it('clears the filter when the active tile is clicked again', async () => {
    const administeredView = mountAdministered();

    const overdueTile = administeredView.findAll('button')
      .find(button => button.text().includes('reservations.dashboard.kpi.overdue'));

    await overdueTile!.trigger('click');
    await overdueTile!.trigger('click');

    expect(overdueTile!.attributes('aria-pressed')).toBe('false');
    expect(administeredView.text()).toContain('Debate club finals');
  });
});

describe('tabs', () => {
  it('shows the requester\'s own reservations by default', () => {
    expect(wrapper.text()).toContain('My workshop');
    expect(wrapper.text()).not.toContain('Debate club finals');
  });

  it('restores the persisted administered tab', () => {
    expect(mountAdministered().text()).toContain('Debate club finals');
  });

  it('keeps the administered tab for a resource manager who has no reservations yet', () => {
    // A manager whose resources nobody has booked still needs the tab — otherwise the page simply
    // never offers them their administration view.
    const nothingBookedYet = createWrapper({ administeredReservations: [] });

    expect(administeredTab(nothingBookedYet)).toBeDefined();
  });

  it('hides the administered tab from someone who manages no resources', () => {
    const requesterOnly = createWrapper({ administeredReservations: [], managedTenants: [] });

    expect(administeredTab(requesterOnly)).toBeUndefined();
  });
});

describe('default view', () => {
  it('hides completed reservations so the open work is not buried', () => {
    const administeredView = mountAdministered();

    // Pending and overdue are open work; the returned one is archive.
    expect(administeredView.text()).toContain('Debate club finals');
    expect(administeredView.text()).toContain('Open lecture: AI');
    expect(administeredView.text()).not.toContain('Spring fair');
  });

  it('surfaces completed reservations once the status filter is cleared', async () => {
    const administeredView = mountAdministered();

    const returnedTile = administeredView.findAll('button')
      .find(button => button.text().includes('reservations.dashboard.kpi.returned'));

    await returnedTile!.trigger('click');

    expect(administeredView.text()).toContain('Spring fair');
  });
});

describe('empty state', () => {
  it('offers a way out when a filter hides everything', async () => {
    const administeredView = mountAdministered();

    await administeredView.find('input').setValue('nothing matches this');

    expect(administeredView.text()).toContain('reservations.dashboard.empty.filtered');

    const clear = administeredView.findAll('button')
      .find(button => button.text().includes('reservations.dashboard.filters.clear'));
    expect(clear).toBeDefined();

    await clear!.trigger('click');

    // Clearing restores the default view rather than leaving the user stuck.
    expect(administeredView.text()).toContain('Debate club finals');
    expect(administeredView.text()).not.toContain('reservations.dashboard.empty.filtered');
  });

  it('says there is nothing here, not nothing matching, when no filter is applied', () => {
    const nothingBookedYet = createWrapper({ administeredReservations: [] });

    expect(nothingBookedYet.text()).not.toContain('reservations.dashboard.empty.filtered');
  });
});

describe('search', () => {
  it('matches on reservation name', async () => {
    const administeredView = mountAdministered();

    await administeredView.find('input').setValue('Debate');

    expect(administeredView.text()).toContain('Debate club finals');
    expect(administeredView.text()).not.toContain('Open lecture: AI');
  });

  it('matches on resource name', async () => {
    const administeredView = mountAdministered();

    await administeredView.find('input').setValue('Resource p-1');

    expect(administeredView.text()).toContain('Debate club finals');
    expect(administeredView.text()).not.toContain('Spring fair');
  });
});
