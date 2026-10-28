import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight, Ban, Check, Undo2, X } from '@lucide/vue';

import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';
import { getBacktrackAction, getCancellablePivotIds, getPrimaryAction, type DashboardReservation } from '@/Utils/ReservationStatus';

const primaryLabels: Record<string, string> = {
  created: 'reservations.actions.approve',
  reserved: 'reservations.actions.hand_over',
  lent: 'reservations.actions.mark_returned',
};

export function reservationRowActions(reservation: DashboardReservation, disabled = false): AdminRecordAction[] {
  const primary = getPrimaryAction(reservation);
  return [
    { key: 'open', label: $t('Atidaryti'), icon: ArrowUpRight, role: 'open', href: route('reservations.show', reservation.id) },
    ...(primary ? [{ key: 'approved', label: $t(primaryLabels[primary.state] ?? 'reservations.actions.approve'), icon: Check, primary: true, disabled }] : []),
    ...(getBacktrackAction(reservation) ? [{ key: 'backtracked', label: $t('reservations.actions.backtrack'), icon: Undo2, disabled }] : []),
    ...(primary?.state === 'created' ? [{ key: 'rejected', label: $t('reservations.actions.reject'), icon: X, destructive: true, disabled }] : []),
    ...(getCancellablePivotIds(reservation).length ? [{ key: 'cancelled', label: $t('reservations.actions.cancel_reservation'), icon: Ban, destructive: true, disabled }] : []),
  ];
}
