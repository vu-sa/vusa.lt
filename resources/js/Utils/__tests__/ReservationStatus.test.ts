import { describe, expect, it } from 'vitest';

import {
  daysOverdue,
  getPrimaryAction,
  getRejectablePivotIds,
  getReservationRowStatus,
  isPivotOverdue,
  isPivotPickupOverdue,
  isReservationSelectable,
  matchesStatusFilter,
  reservationDaysOverdue,
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

describe('overdue rules', () => {
  it('treats a lent item past its end time as overdue', () => {
    const lentLate = pivot({ state: 'lent', end_time: '2026-07-11T10:00:00Z' });

    expect(isPivotOverdue(lentLate, NOW)).toBe(true);
    expect(daysOverdue(lentLate, NOW)).toBe(2);
  });

  it('does not treat a returned item past its end time as overdue', () => {
    // ReservationResourceCard omits the state guard today and gets this wrong.
    const returnedLate = pivot({ state: 'returned', end_time: '2026-07-11T10:00:00Z' });

    expect(isPivotOverdue(returnedLate, NOW)).toBe(false);
    expect(daysOverdue(returnedLate, NOW)).toBe(0);
  });

  it('does not treat a lent item still within its period as overdue', () => {
    expect(isPivotOverdue(pivot({ state: 'lent' }), NOW)).toBe(false);
  });

  it('flags a reserved item that was never picked up', () => {
    const uncollected = pivot({ state: 'reserved', start_time: '2026-07-01T10:00:00Z' });

    expect(isPivotPickupOverdue(uncollected, NOW)).toBe(true);
    expect(isPivotPickupOverdue(pivot({ state: 'lent', start_time: '2026-07-01T10:00:00Z' }), NOW)).toBe(false);
  });
});

describe('getReservationRowStatus', () => {
  it('ranks anything awaiting review above an overdue loan', () => {
    const row = reservation([
      pivot({ state: 'lent', end_time: '2026-07-01T10:00:00Z' }),
      pivot({ state: 'created' }),
    ]);

    expect(getReservationRowStatus(row, {}, NOW)).toBe('created');
  });

  it('ranks an overdue loan above a healthy one', () => {
    const row = reservation([
      pivot({ state: 'lent' }),
      pivot({ state: 'lent', end_time: '2026-07-01T10:00:00Z' }),
    ]);

    expect(getReservationRowStatus(row, {}, NOW)).toBe('overdue');
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

    expect(reservationDaysOverdue(row, {}, NOW)).toBe(10);
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
    // A row holding an overdue loan AND an uncollected reservation badges as "overdue". The button
    // must chase the overdue item, not hand over the other one — otherwise badge and button lie.
    const row = reservation([
      pivot({ id: 'p-overdue', state: 'lent', end_time: '2026-07-01T10:00:00Z' }),
      pivot({ id: 'p-reserved', state: 'reserved' }),
    ]);

    expect(getReservationRowStatus(row, { approvableOnly: true }, NOW)).toBe('overdue');
    expect(getPrimaryAction(row, NOW)).toEqual({ state: 'lent', pivotIds: ['p-overdue'] });
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
  const overdue = reservation([pivot({ state: 'lent', end_time: '2026-07-01T10:00:00Z' })]);
  const returned = reservation([pivot({ state: 'returned' })]);

  it('counts an overdue item as lent, since that is exactly what it is', () => {
    // The KPI counts overdue items in both buckets; the filter has to agree, or "Lent 29" would
    // show fewer than 29 rows.
    expect(matchesStatusFilter(overdue, 'lent', {}, NOW)).toBe(true);
    expect(matchesStatusFilter(lent, 'lent', {}, NOW)).toBe(true);
    expect(matchesStatusFilter(pending, 'lent', {}, NOW)).toBe(false);
  });

  it('keeps overdue as its own narrower filter', () => {
    expect(matchesStatusFilter(overdue, 'overdue', {}, NOW)).toBe(true);
    expect(matchesStatusFilter(lent, 'overdue', {}, NOW)).toBe(false);
  });

  it('hides terminal reservations from the active view', () => {
    expect(matchesStatusFilter(pending, 'active', {}, NOW)).toBe(true);
    expect(matchesStatusFilter(overdue, 'active', {}, NOW)).toBe(true);
    expect(matchesStatusFilter(returned, 'active', {}, NOW)).toBe(false);

    expect(matchesStatusFilter(returned, 'all', {}, NOW)).toBe(true);
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
