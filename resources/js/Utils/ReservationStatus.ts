/**
 * Status derivation for reservations and their resources.
 *
 * Approval permission lives on the `reservation_resource` pivot, not the reservation: one
 * reservation may hold resources from several tenants, and an administrator may only act on the
 * items whose resource belongs to a tenant they manage. Every function here therefore works on
 * pivots, and callers opt in to the `approvable` subset when they render an administrator's view.
 *
 * The backend state chain is `created → reserved → lent → returned`, plus `created → rejected`
 * and `created|reserved → cancelled`. "Unresolved" is not a state — it is any active pivot
 * (`created`, `reserved`, or `lent`) whose reserved time window has already passed.
 */

export type ReservationResourceState
  = | 'created'
    | 'reserved'
    | 'lent'
    | 'returned'
    | 'rejected'
    | 'cancelled';

/** A status you can filter or colour by — always a real stored state, never a derived one. */
export type ReservationRowStatus = ReservationResourceState;

/** One of the states a reservation's items are in, and how many of them are in it. */
export interface ReservationStateCount {
  state: ReservationResourceState;
  count: number;
}

/** The three buckets the KPI strip reports, in the order work arrives. */
export type KpiStatus = Extract<ReservationRowStatus, 'created' | 'lent' | 'returned'>;

/** KPI counts, derived on the client by computeReservationStats(). Counts reservations, not items. */
export interface ReservationStats {
  awaiting: number;
  awaitingDueSoon: number;
  awaitingUnresolved: number;
  lent: number;
  lentQuantity: number;
  lentUnresolved: number;
  returnedLast30Days: number;
  maxDaysLate: number;
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
 * The unresolved checks take only the fields they read, so they also accept the generated
 * `App.Entities.ReservationResource` pivot and both tables can share one definition of "unresolved".
 */
type ReturnCheckable = Pick<ReservationPivot, 'state' | 'end_time'>;
type PickupCheckable = Pick<ReservationPivot, 'state' | 'start_time'>;

/**
 * An active pivot (`created`, `reserved`, or `lent`) whose reserved end time has passed.
 * The reservation is still open in the system even though its window is over.
 */
export function isPivotUnresolved(pivot: ReturnCheckable, now: Date = new Date()): boolean {
  if (!['created', 'reserved', 'lent'].includes(pivot.state)) {
    return false;
  }

  return new Date(pivot.end_time) < now;
}

/** A reserved item that was never picked up by its start time. */
export function isPivotPickupOverdue(pivot: PickupCheckable, now: Date = new Date()): boolean {
  return pivot.state === 'reserved' && new Date(pivot.start_time) < now;
}

export function daysUnresolved(pivot: ReturnCheckable, now: Date = new Date()): number {
  if (!isPivotUnresolved(pivot, now)) {
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

/** Every state, in the order items move through them. Orders the badge's breakdown. */
const STATE_CHAIN: ReservationResourceState[] = [
  'created',
  'reserved',
  'lent',
  'returned',
  'rejected',
  'cancelled',
];

/**
 * Every state a reservation's items are in, with how many items sit in each.
 *
 * A reservation is a bundle: one item can be out on loan while another still waits for approval.
 * Collapsing that to a single status hid half the work, so the row reports all of it and the UI
 * marks a reservation with more than one state as mixed.
 *
 * Empty when the scope leaves no pivots at all (an administrator looking at a reservation made up
 * entirely of foreign items).
 *
 * "Unresolved" is intentionally not one of these — it is a lateness indicator, not a state.
 */
export function getReservationStates(
  reservation: DashboardReservation,
  options: ScopeOptions = {},
): ReservationStateCount[] {
  return summarizeStates(scopedPivots(reservation, options).map(pivot => pivot.state));
}

/**
 * The same breakdown from a bare list of item states, for callers holding an
 * `App.Entities.Reservation` rather than the dashboard's payload shape.
 */
export function summarizeStates(states: ReservationResourceState[]): ReservationStateCount[] {
  return STATE_CHAIN
    .map(state => ({ state, count: states.filter(present => present === state).length }))
    .filter(({ count }) => count > 0);
}

/** Whether any of the reservation's items in scope are in this state. */
function hasState(
  reservation: DashboardReservation,
  state: ReservationResourceState,
  options: ScopeOptions = {},
): boolean {
  return scopedPivots(reservation, options).some(pivot => pivot.state === state);
}

/**
 * The stage the row's single primary button acts on: the earliest one still open
 * (`created`, then `reserved`, then `lent`), so approvals are never left blocking a hand-over.
 */
function getPrimaryActionableState(
  reservation: DashboardReservation,
  options: ScopeOptions = {},
): ReservationResourceState | null {
  return ACTIONABLE_STATES.find(state => hasState(reservation, state, options)) ?? null;
}

/** Whether any scoped pivot is past its reserved window. */
export function isReservationUnresolved(
  reservation: DashboardReservation,
  options: ScopeOptions = {},
  now: Date = new Date(),
): boolean {
  return scopedPivots(reservation, options).some(pivot => isPivotUnresolved(pivot, now));
}

/** The largest lateness across a row's items, for the "N days late" sub-line. */
export function reservationDaysUnresolved(
  reservation: DashboardReservation,
  options: ScopeOptions = {},
  now: Date = new Date(),
): number {
  return scopedPivots(reservation, options)
    .reduce((worst, pivot) => Math.max(worst, daysUnresolved(pivot, now)), 0);
}

/**
 * The action the row's primary button performs, and the pivots it will submit.
 *
 * A reservation can hold items in several states at once — the badge reports them all, and the
 * button acts on the earliest stage still open, submitting only the items actually in it.
 */
export function getPrimaryAction(
  reservation: DashboardReservation,
): { state: ReservationResourceState; pivotIds: string[] } | null {
  const state = getPrimaryActionableState(reservation, { approvableOnly: true });

  if (state === null) {
    return null;
  }

  const matching = scopedPivots(reservation, { approvableOnly: true })
    .filter(pivot => pivot.state === state);

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

/** A reservation still has open business in this scope: any of its items is yet to be finished. */
export function isReservationActive(
  reservation: DashboardReservation,
  options: ScopeOptions = {},
): boolean {
  return scopedPivots(reservation, options).some(pivot => ACTIONABLE_STATES.includes(pivot.state));
}

/**
 * Does a reservation pass the status dropdown / KPI filter?
 *
 * Membership, not equality: a reservation holding one item awaiting approval and another already
 * out on loan is genuinely doing both, so it shows up under either filter.
 */
export function matchesStatusFilter(
  reservation: DashboardReservation,
  filter: StatusFilter,
  options: ScopeOptions = {},
): boolean {
  if (filter === 'all') {
    return true;
  }
  if (filter === 'active') {
    return isReservationActive(reservation, options);
  }

  return hasState(reservation, filter, options);
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
 * table, so "Lent 5" has to mean "5 rows appear when you click this". Item counts still appear in
 * the captions, where they inform rather than mislead.
 *
 * Counted by membership, matching matchesStatusFilter(): a reservation with an item awaiting
 * approval and another already out counts in both tiles, because clicking either one shows it.
 * The tiles therefore need not sum to the number of rows.
 *
 * It runs client-side, over the already search- and tenant-filtered list, so the numbers track
 * whatever the user has narrowed the table down to.
 */
export function computeReservationStats(
  reservations: DashboardReservation[],
  options: ScopeOptions = {},
  now: Date = new Date(),
): ReservationStats {
  const awaiting = reservations.filter(reservation => hasState(reservation, 'created', options));
  const lent = reservations.filter(reservation => hasState(reservation, 'lent', options));

  const dueSoonCutoff = new Date(now.getTime() + DUE_SOON_HOURS * 60 * 60 * 1000);
  const returnedCutoff = new Date(now.getTime() - RETURNED_WINDOW_DAYS * DAY_MS);

  const returnedRecently = reservations.filter(reservation =>
    scopedPivots(reservation, options).some((pivot) => {
      if (pivot.state !== 'returned') {
        return false;
      }

      const at = new Date(pivot.returned_at ?? pivot.updated_at);

      return at >= returnedCutoff;
    }),
  );

  const lentQuantity = lent.reduce(
    (total, reservation) => total + scopedPivots(reservation, options)
      .filter(pivot => pivot.state === 'lent')
      .reduce((sum, pivot) => sum + (pivot.quantity ?? 1), 0),
    0,
  );

  return {
    awaiting: awaiting.length,
    awaitingDueSoon: awaiting.filter((reservation) => {
      const start = new Date(reservation.start_time);

      return start >= now && start <= dueSoonCutoff;
    }).length,
    awaitingUnresolved: awaiting.filter(reservation => isReservationUnresolved(reservation, options, now)).length,
    lent: lent.length,
    lentQuantity,
    lentUnresolved: lent.filter(reservation => isReservationUnresolved(reservation, options, now)).length,
    returnedLast30Days: returnedRecently.length,
    maxDaysLate: reservations.reduce(
      (worst, reservation) => Math.max(worst, reservationDaysUnresolved(reservation, options, now)),
      0,
    ),
  };
}
