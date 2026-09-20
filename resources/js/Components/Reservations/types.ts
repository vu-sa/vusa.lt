/** `resolved` is not a server decision — it fast-forwards items to their final approved state. */
export type ReservationDecision = 'approved' | 'rejected' | 'cancelled' | 'resolved' | 'backtracked';
