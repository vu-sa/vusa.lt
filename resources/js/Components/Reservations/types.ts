/** `resolved` is not a server decision — it fast-forwards items to their final approved state. */
export type ReservationDecision = 'approved' | 'rejected' | 'cancelled' | 'resolved' | 'backtracked';

/** Why a cart line cannot be submitted as it stands. */
export type ReservationCartProblem = 'unavailable' | 'not_reservable' | 'removed';

export interface ReservationCartItem {
  id: number;
  resource_id: string;
  name: string | null;
  tenant_shortname: string | null;
  image_url: string | null;
  capacity: number;
  quantity: number;
  /** Free for the cart's period; null until a period is picked. */
  available: number | null;
  problem: ReservationCartProblem | null;
}

/** `SerializeReservationCart` — the user's unfinished reservation. */
export interface ReservationCart {
  name: string | null;
  description: string | null;
  start_time: number | null;
  end_time: number | null;
  count: number;
  problemCount: number;
  expiresAt: string | null;
  ttlDays: number;
  items: ReservationCartItem[];
}
