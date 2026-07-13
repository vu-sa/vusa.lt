import { describe, expect, it } from 'vitest';

import {
  computeReservationStats,
  daysUnresolved,
  getPrimaryAction,
  getRejectablePivotIds,
  getReservationRowStatus,
  isPivotPickupOverdue,
  isPivotUnresolved,
  isReservationSelectable,
  isReservationUnresolved,
  matchesStatusFilter,
  reservationDaysUnresolved,
  type DashboardReservation,
  type ReservationPivot,
  type ReservationResourceState,
} from '../ReservationStatus';

const NOW = new Date('2026-07-13T12:00:00Z');

function pivot(overrides: Partial<ReservationPivot> & { state: ReservationResourceState }): ReservationPivot {
  return {
    id: Math.random().toString(36).slice(2),
    start_time: '2026-07-10T10:00:00Z',
    end_time: '2026-07-20T10:00:00Z',
    returned_at: null,
    updated_at: '2026-07-01T10:00:00Z',
    quantity: 1,
    state_properties: { tagType: 'info', description: '' },
    approvable: true,
    cancellable: false,
    ...overrides,
  };
}

function reservation(pivots: ReservationPivot[]): DashboardReservation {
  return {
    id: 'res-1',
    name: 'Fresher\'s camp',
    description: null,
    start_time: '2026-07-10T10:00:00Z',
    end_time: '2026-07-20T10:00:00Z',
    created_at: '2026-07-01T10:00:00Z',
    users: [],
    resources: pivots.map((p, index) => ({
      id: `resource-${index}`,
      name: `Resource ${index}`,
      tenant: { id: 'tenant-1', shortname: 'VU SA MIF' },
      pivot: p,
    })),
  };
}

describe('unresolved rules', () => {
  it('treats a lent item past its end time as unresolved', () => {
    const lentLate = pivot({ state: 'lent', end_time: '2026-07-11T10:00:00Z' });

    expect(isPivotUnresolved(lentLate, NOW)).toBe(true);
    expect(daysUnresolved(lentLate, NOW)).toBe(2);
  });

  it('does not treat a returned item past its end time as unresolved', () => {
    const returnedLate = pivot({ state: 'returned', end_time: '2026-07-11T10:00:00Z' });

    expect(isPivotUnresolved(returnedLate, NOW)).toBe(false);
    expect(daysUnresolved(returnedLate, NOW)).toBe(0);
  });

  it('does not treat a lent item still within its period as unresolved', () => {
    expect(isPivotUnresolved(pivot({ state: 'lent' }), NOW)).toBe(false);
  });

  it('treats any active state past its end time as unresolved', () => {
    expect(isPivotUnresolved(pivot({ state: 'created', end_time: '2026-07-11T10:00:00Z' }), NOW)).toBe(true);
    expect(isPivotUnresolved(pivot({ state: 'reserved', end_time: '2026-07-11T10:00:00Z' }), NOW)).toBe(true);
  });

  it('flags a reserved item that was never picked up', () => {
    const uncollected = pivot({ state: 'reserved', start_time: '2026-07-01T10:00:00Z' });

    expect(isPivotPickupOverdue(uncollected, NOW)).toBe(true);
    expect(isPivotPickupOverdue(pivot({ state: 'lent', start_time: '2026-07-01T10:00:00Z' }), NOW)).toBe(false);
  });
});

describe('getReservationRowStatus', () => {
  it('keeps the real state even when a loan is past its end time', () => {
    const row = reservation([
      pivot({ state: 'lent' }),
      pivot({ state: 'lent', end_time: '2026-07-01T10:00:00Z' }),
    ]);

    expect(getReservationRowStatus(row, {}, NOW)).toBe('lent');
  });

  it('only reports a terminal status once nothing is live', () => {
    expect(getReservationRowStatus(reservation([pivot({ state: 'returned' })]), {}, NOW)).toBe('returned');
    expect(getReservationRowStatus(
      reservation([pivot({ state: 'returned' }), pivot({ state: 'reserved' })]),
      {},
      NOW,
    )).toBe('reserved');
  });

  it('scopes the status to the pivots the administrator owns', () => {
    const row = reservation([
      pivot({ state: 'created', approvable: false }),
      pivot({ state: 'lent', approvable: true }),
    ]);

    expect(getReservationRowStatus(row, {}, NOW)).toBe('created');
    expect(getReservationRowStatus(row, { approvableOnly: true }, NOW)).toBe('lent');
  });

  it('returns null when the scope leaves no pivots', () => {
    const foreign = reservation([pivot({ state: 'created', approvable: false })]);

    expect(getReservationRowStatus(foreign, { approvableOnly: true }, NOW)).toBeNull();
  });

  it('reports the worst lateness across the row', () => {
    const row = reservation([
      pivot({ state: 'lent', end_time: '2026-07-11T10:00:00Z' }),
      pivot({ state: 'lent', end_time: '2026-07-03T10:00:00Z' }),
    ]);

    expect(reservationDaysUnresolved(row, {}, NOW)).toBe(10);
  });
});

describe('isReservationUnresolved', () => {
  it('is true when any scoped pivot is past its end time', () => {
    const row = reservation([
      pivot({ state: 'lent', end_time: '2026-07-01T10:00:00Z' }),
      pivot({ state: 'created' }),
    ]);

    expect(isReservationUnresolved(row, {}, NOW)).toBe(true);
  });

  it('is false when all pivots are on time or terminal', () => {
    expect(isReservationUnresolved(reservation([pivot({ state: 'lent' })]), {}, NOW)).toBe(false);
    expect(isReservationUnresolved(reservation([pivot({ state: 'returned' })]), {}, NOW)).toBe(false);
  });
});

describe('getPrimaryAction', () => {
  it('acts on the most urgent group only, so the label matches the submission', () => {
    const row = reservation([
      pivot({ id: 'p-lent', state: 'lent' }),
      pivot({ id: 'p-created', state: 'created' }),
    ]);

    expect(getPrimaryAction(row, NOW)).toEqual({ state: 'created', pivotIds: ['p-created'] });
  });

  it('advances reserved items to hand over once nothing is pending', () => {
    const row = reservation([
      pivot({ id: 'p-reserved', state: 'reserved' }),
      pivot({ id: 'p-returned', state: 'returned' }),
    ]);

    expect(getPrimaryAction(row, NOW)).toEqual({ state: 'reserved', pivotIds: ['p-reserved'] });
  });

  it('always acts on the state the row badge reports', () => {
    // A row holding an unresolved loan AND an uncollected reservation still badges as "lent" now;
    // unresolved is only an indicator. The button follows the real state.
    const row = reservation([
      pivot({ id: 'p-lent', state: 'lent', end_time: '2026-07-01T10:00:00Z' }),
      pivot({ id: 'p-reserved', state: 'reserved' }),
    ]);

    expect(getReservationRowStatus(row, { approvableOnly: true }, NOW)).toBe('lent');
    expect(getPrimaryAction(row, NOW)).toEqual({ state: 'lent', pivotIds: ['p-lent'] });
  });

  it('ignores pivots the administrator does not own', () => {
    const row = reservation([
      pivot({ id: 'p-foreign', state: 'created', approvable: false }),
      pivot({ id: 'p-mine', state: 'lent', approvable: true }),
    ]);

    expect(getPrimaryAction(row, NOW)).toEqual({ state: 'lent', pivotIds: ['p-mine'] });
  });

  it('returns null when there is nothing left to advance', () => {
    expect(getPrimaryAction(reservation([pivot({ state: 'returned' })]), NOW)).toBeNull();
  });
});

describe('matchesStatusFilter', () => {
  const pending = reservation([pivot({ state: 'created' })]);
  const lent = reservation([pivot({ state: 'lent' })]);
  const lateLent = reservation([pivot({ state: 'lent', end_time: '2026-07-01T10:00:00Z' })]);
  const returned = reservation([pivot({ state: 'returned' })]);

  it('counts a late item by its real state, not as a separate unresolved filter', () => {
    expect(matchesStatusFilter(lateLent, 'lent', {}, NOW)).toBe(true);
    expect(matchesStatusFilter(lent, 'lent', {}, NOW)).toBe(true);
    expect(matchesStatusFilter(pending, 'lent', {}, NOW)).toBe(false);
  });

  it('hides terminal reservations from the active view', () => {
    expect(matchesStatusFilter(pending, 'active', {}, NOW)).toBe(true);
    expect(matchesStatusFilter(lateLent, 'active', {}, NOW)).toBe(true);
    expect(matchesStatusFilter(returned, 'active', {}, NOW)).toBe(false);

    expect(matchesStatusFilter(returned, 'all', {}, NOW)).toBe(true);
  });
});

describe('computeReservationStats', () => {
  it('counts unresolved items inside the awaiting and lent buckets', () => {
    const rows = [
      reservation([pivot({ state: 'created', end_time: '2026-07-01T10:00:00Z' })]),
      reservation([pivot({ state: 'lent', end_time: '2026-07-01T10:00:00Z' })]),
      reservation([pivot({ state: 'created' })]),
      reservation([pivot({ state: 'returned' })]),
    ];

    const stats = computeReservationStats(rows, {}, NOW);

    expect(stats.awaiting).toBe(2);
    expect(stats.awaitingUnresolved).toBe(1);
    expect(stats.lent).toBe(1);
    expect(stats.lentUnresolved).toBe(1);
    expect(stats.returnedLast30Days).toBe(1);
  });
});

describe('selection and rejection', () => {
  it('only allows selecting rows with something to act on', () => {
    expect(isReservationSelectable(reservation([pivot({ state: 'created' })]))).toBe(true);
    expect(isReservationSelectable(reservation([pivot({ state: 'returned' })]))).toBe(false);
    expect(isReservationSelectable(reservation([pivot({ state: 'created', approvable: false })]))).toBe(false);
  });

  it('narrows a reject to pending pivots, since that is the only legal transition', () => {
    const row = reservation([
      pivot({ id: 'p-created', state: 'created' }),
      pivot({ id: 'p-lent', state: 'lent' }),
      pivot({ id: 'p-reserved', state: 'reserved' }),
    ]);

    expect(getRejectablePivotIds(row)).toEqual(['p-created']);
  });
});
