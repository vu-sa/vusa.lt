/**
 * Status derivation for reservations and their resources.
 *
 * Approval permission lives on the `reservation_resource` pivot, not the reservation: one
 * reservation may hold resources from several tenants, and an administrator may only act on the
 * items whose resource belongs to a tenant they manage. Every function here therefore works on
 * pivots, and callers opt in to the `approvable` subset when they render an administrator's view.
 *
 * The backend state chain is `created → reserved → lent → returned`, plus `created → rejected`
 * and `created|reserved → cancelled`. "Overdue" is not a state — it is a lent item whose return
 * time has passed.
 */

export type ReservationResourceState
  = | 'created'
    | 'reserved'
    | 'lent'
    | 'returned'
    | 'rejected'
    | 'cancelled';

/** `overdue` is display-only; the server never stores it. */
export type ReservationRowStatus = ReservationResourceState | 'overdue';

/** The four buckets the KPI strip reports, in the order work arrives. */
export type KpiStatus = Extract<ReservationRowStatus, 'created' | 'lent' | 'overdue' | 'returned'>;

/** KPI counts, derived on the client by computeReservationStats(). Counts reservations, not items. */
export interface ReservationStats {
  awaiting: number;
  awaitingDueSoon: number;
  lent: number;
  lentQuantity: number;
  overdue: number;
  overdueMaxDaysLate: number;
  returnedLast30Days: number;
}

/** The states an administrator can still advance. Ordered by which action takes priority. */
export const ACTIONABLE_STATES: ReservationResourceState[] = ['created', 'reserved', 'lent'];

/** States nobody can act on any more. Hidden by default — they are archive, not work. */
export const TERMINAL_STATUSES: ReservationRowStatus[] = ['returned', 'rejected', 'cancelled'];

/**
 * The status dropdown offers these on top of the concrete states. `active` is the default: a
 * manager opening the page wants the work, not a wall of reservations that closed two years ago.
 */
export type StatusFilter = ReservationRowStatus | 'all' | 'active';

export interface ReservationPivot {
  id: number | string;
  start_time: string;
  end_time: string;
  returned_at: string | null;
  /** Fallback for returned_at, which is only stamped on items returned since that was added. */
  updated_at: string;
  quantity: number;
  state: ReservationResourceState;
  state_properties?: { tagType: string; description: string };
  /** The current user manages this resource's tenant, so may approve/reject/hand over/return it. */
  approvable: boolean;
  /** The current user is on the reservation and the item can still be called off. */
  cancellable: boolean;
}

export interface ReservationResource {
  id: string;
  name: string;
  tenant: { id: string; shortname: string } | null;
  pivot: ReservationPivot;
}

export interface DashboardReservation {
  id: string;
  name: string;
  description: string | null;
  start_time: string;
  end_time: string;
  created_at: string;
  users: App.Entities.User[];
  resources: ReservationResource[];
}

export interface ScopeOptions {
  /** Restrict to the pivots the current user administers. */
  approvableOnly?: boolean;
}

/**
 * The overdue checks take only the fields they read, so they also accept the generated
 * `App.Entities.ReservationResource` pivot and both tables can share one definition of "overdue".
 */
type ReturnCheckable = Pick<ReservationPivot, 'state' | 'end_time'>;
type PickupCheckable = Pick<ReservationPivot, 'state' | 'start_time'>;

/** A lent item whose return time has passed. */
export function isPivotOverdue(pivot: ReturnCheckable, now: Date = new Date()): boolean {
  return pivot.state === 'lent' && new Date(pivot.end_time) < now;
}

/** A reserved item that was never picked up by its start time. */
export function isPivotPickupOverdue(pivot: PickupCheckable, now: Date = new Date()): boolean {
  return pivot.state === 'reserved' && new Date(pivot.start_time) < now;
}

export function daysOverdue(pivot: ReturnCheckable, now: Date = new Date()): number {
  if (!isPivotOverdue(pivot, now)) {
    return 0;
  }

  const elapsedMs = now.getTime() - new Date(pivot.end_time).getTime();

  return Math.floor(elapsedMs / (1000 * 60 * 60 * 24));
}

function scopedPivots(reservation: DashboardReservation, options: ScopeOptions = {}): ReservationPivot[] {
  return reservation.resources
    .map(resource => resource.pivot)
    .filter(pivot => !options.approvableOnly || pivot.approvable);
}

/**
 * Collapse a reservation's pivots into the single status shown on its row.
 *
 * Priority runs "needs attention" first: anything awaiting review outranks an overdue item, which
 * outranks an active loan, and terminal states only surface once nothing is live. Returns null when
 * the scope leaves no pivots at all (an administrator looking at a reservation of foreign items).
 */
export function getReservationRowStatus(
  reservation: DashboardReservation,
  options: ScopeOptions = {},
  now: Date = new Date(),
): ReservationRowStatus | null {
  const pivots = scopedPivots(reservation, options);

  if (pivots.length === 0) {
    return null;
  }

  if (pivots.some(pivot => pivot.state === 'created')) {
    return 'created';
  }
  if (pivots.some(pivot => isPivotOverdue(pivot, now))) {
    return 'overdue';
  }
  if (pivots.some(pivot => pivot.state === 'lent')) {
    return 'lent';
  }
  if (pivots.some(pivot => pivot.state === 'reserved')) {
    return 'reserved';
  }
  if (pivots.some(pivot => pivot.state === 'returned')) {
    return 'returned';
  }
  if (pivots.some(pivot => pivot.state === 'rejected')) {
    return 'rejected';
  }

  return 'cancelled';
}

/** The largest lateness across a row's items, for the "N days overdue" sub-line. */
export function reservationDaysOverdue(
  reservation: DashboardReservation,
  options: ScopeOptions = {},
  now: Date = new Date(),
): number {
  return scopedPivots(reservation, options)
    .reduce((worst, pivot) => Math.max(worst, daysOverdue(pivot, now)), 0);
}

/**
 * The action the row's primary button performs, and the pivots it will submit.
 *
 * A reservation can hold items in several states at once. The action deliberately follows the row's
 * *status*, so the badge and the button can never disagree — a row badged "Overdue" whose button
 * hands over some unrelated item is worse than useless.
 */
export function getPrimaryAction(
  reservation: DashboardReservation,
  now: Date = new Date(),
): { state: ReservationResourceState; pivotIds: string[] } | null {
  const status = getReservationRowStatus(reservation, { approvableOnly: true }, now);

  if (status === null) {
    return null;
  }

  // Overdue is a lent item that is late; the action on it is still "mark returned".
  const state = (status === 'overdue' ? 'lent' : status) as ReservationResourceState;

  if (!ACTIONABLE_STATES.includes(state)) {
    return null;
  }

  const matching = scopedPivots(reservation, { approvableOnly: true })
    .filter(pivot => pivot.state === state);

  if (matching.length === 0) {
    return null;
  }

  return { state, pivotIds: matching.map(pivot => String(pivot.id)) };
}

/** Pivot IDs in the given states that the current user may act on. Used by row and bulk actions. */
export function getActionablePivotIds(
  reservation: DashboardReservation,
  states: ReservationResourceState[] = ACTIONABLE_STATES,
): string[] {
  return scopedPivots(reservation, { approvableOnly: true })
    .filter(pivot => states.includes(pivot.state))
    .map(pivot => String(pivot.id));
}

/** Pivot IDs the owner may still call off. */
export function getCancellablePivotIds(reservation: DashboardReservation): string[] {
  return reservation.resources
    .map(resource => resource.pivot)
    .filter(pivot => pivot.cancellable)
    .map(pivot => String(pivot.id));
}

/** Rows with nothing to act on cannot be selected for a bulk action. */
export function isReservationSelectable(reservation: DashboardReservation): boolean {
  return getActionablePivotIds(reservation).length > 0;
}

/**
 * Rejection is only a legal transition out of `created`, so a bulk reject must narrow the
 * selection rather than submit everything and let the server drop the rest.
 */
export function getRejectablePivotIds(reservation: DashboardReservation): string[] {
  return getActionablePivotIds(reservation, ['created']);
}

/** A reservation still has open business in this scope. */
export function isReservationActive(
  reservation: DashboardReservation,
  options: ScopeOptions = {},
  now: Date = new Date(),
): boolean {
  const status = getReservationRowStatus(reservation, options, now);

  return status !== null && !TERMINAL_STATUSES.includes(status);
}

/** Does a reservation pass the status dropdown / KPI filter? */
export function matchesStatusFilter(
  reservation: DashboardReservation,
  filter: StatusFilter,
  options: ScopeOptions = {},
  now: Date = new Date(),
): boolean {
  if (filter === 'all') {
    return true;
  }
  if (filter === 'active') {
    return isReservationActive(reservation, options, now);
  }

  const status = getReservationRowStatus(reservation, options, now);

  // Overdue is a lent item that is late, not a state of its own: it belongs in both buckets, which
  // is how the KPI counts it too. Filtering by "lent" must not hide the lent items that are late.
  if (filter === 'lent') {
    return status === 'lent' || status === 'overdue';
  }

  return status === filter;
}

/**
 * The dot colour that stands for each status across the page — the KPI tiles and the status
 * dropdown use the same one, so a selected filter is recognisable at a glance rather than only
 * from a subtle background change.
 */
const STATUS_DOT_CLASSES: Record<ReservationRowStatus, string> = {
  created: 'bg-amber-500',
  reserved: 'bg-sky-500',
  lent: 'bg-zinc-900 dark:bg-zinc-100',
  overdue: 'bg-rose-500',
  returned: 'bg-emerald-500',
  rejected: 'bg-rose-600',
  cancelled: 'bg-zinc-400',
};

export function getStatusDotClass(status: ReservationRowStatus): string {
  return STATUS_DOT_CLASSES[status];
}

/**
 * `active` is not one status but every open one, so it is shown as a small stack of their dots
 * rather than a single colour that would misrepresent it.
 */
export const ACTIVE_STATUS_DOT_CLASSES: string[] = [
  STATUS_DOT_CLASSES.created,
  STATUS_DOT_CLASSES.reserved,
  STATUS_DOT_CLASSES.lent,
];

const DAY_MS = 24 * 60 * 60 * 1000;
const RETURNED_WINDOW_DAYS = 30;
const DUE_SOON_HOURS = 48;

/**
 * KPI counts over the reservations the table is showing.
 *
 * Deliberately counts *reservations*, not the resource rows underneath them: the tiles filter the
 * table, so "Overdue 2" has to mean "2 rows appear when you click this". Counting items instead
 * made the tile read 10 while the table showed 2. Item counts still appear in the captions, where
 * they inform rather than mislead.
 *
 * It runs client-side, over the already search- and tenant-filtered list, so the numbers track
 * whatever the user has narrowed the table down to.
 */
export function computeReservationStats(
  reservations: DashboardReservation[],
  options: ScopeOptions = {},
  now: Date = new Date(),
): ReservationStats {
  const rows = reservations.map(reservation => ({
    reservation,
    status: getReservationRowStatus(reservation, options, now),
  }));

  const awaiting = rows.filter(row => row.status === 'created');
  const overdue = rows.filter(row => row.status === 'overdue');
  // An overdue item is still lent, so it belongs in both — exactly as the filter treats it.
  const lent = rows.filter(row => row.status === 'lent' || row.status === 'overdue');

  const dueSoonCutoff = new Date(now.getTime() + DUE_SOON_HOURS * 60 * 60 * 1000);
  const returnedCutoff = new Date(now.getTime() - RETURNED_WINDOW_DAYS * DAY_MS);

  const returnedRecently = rows.filter(row =>
    row.status === 'returned'
    && scopedPivots(row.reservation, options).some((pivot) => {
      if (pivot.state !== 'returned') {
        return false;
      }

      const at = new Date(pivot.returned_at ?? pivot.updated_at);

      return at >= returnedCutoff;
    }),
  );

  const lentQuantity = lent.reduce(
    (total, row) => total + scopedPivots(row.reservation, options)
      .filter(pivot => pivot.state === 'lent')
      .reduce((sum, pivot) => sum + (pivot.quantity ?? 1), 0),
    0,
  );

  return {
    awaiting: awaiting.length,
    awaitingDueSoon: awaiting.filter((row) => {
      const start = new Date(row.reservation.start_time);

      return start >= now && start <= dueSoonCutoff;
    }).length,
    lent: lent.length,
    lentQuantity,
    overdue: overdue.length,
    overdueMaxDaysLate: overdue.reduce(
      (worst, row) => Math.max(worst, reservationDaysOverdue(row.reservation, options, now)),
      0,
    ),
    returnedLast30Days: returnedRecently.length,
  };
}
