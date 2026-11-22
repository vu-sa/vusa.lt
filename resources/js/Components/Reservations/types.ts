import type { DashboardReservation, ReservationPivot, ReservationResource } from '@/Utils/ReservationStatus';

export type { ReservationAvailabilityRow } from '@/Utils/ReservationStatus';

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

export interface ReservationCartSummary {
  name: string | null;
  count: number;
  start_time: number | null;
  end_time: number | null;
}

export type ReservationDraftSummaryData = ReservationCartSummary & Partial<Pick<ReservationCart, 'problemCount'>>;

export interface ReservationCart extends ReservationCartSummary {
  description: string | null;
  problemCount: number;
  expiresAt: string | null;
  ttlDays: number;
  items: ReservationCartItem[];
}

export interface ReservationTableResource extends ReservationResource {
  tenant: { id: number; shortname: string };
  pivot: ReservationPivot & {
    id: number;
    reservation_id: string;
    resource_id: string;
    state_properties: { tagType: string; description: string };
  };
}

export interface ReservationTableRow extends DashboardReservation {
  updated_at: string;
  resources: ReservationTableResource[];
}

export interface ResourceManager {
  id: string;
  name: string;
  email: string | null;
  phone: string | null;
  facebook_url: string | null;
  profile_photo_path: string | null;
}
