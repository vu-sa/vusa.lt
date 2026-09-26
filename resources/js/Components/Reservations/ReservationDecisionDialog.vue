<template>
  <!--
    Every decision — row-level or bulk — goes through this dialog. Approving advances each item one
    step along created → reserved → lent → returned, so the operator has to see what their click is
    about to do before it happens, and can attach a note explaining why.
  -->
  <Dialog :open @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-lg" data-slot="reservation-decision-dialog">
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription>{{ description }}</DialogDescription>
      </DialogHeader>

      <div class="flex flex-col gap-4">
        <div :class="['flex flex-col gap-3 border p-3 text-sm', isDestructive ? 'border-destructive/40' : 'border-border']">
          <div v-for="group in groups" :key="group.state" class="flex flex-col gap-1">
            <div :class="['font-medium', isDestructive ? 'text-destructive' : 'text-muted-foreground']">
              {{ group.label }}
            </div>
            <ul class="ml-4 flex flex-col gap-0.5">
              <li v-for="item in group.items" :key="item.key" class="flex items-center gap-2">
                <span :class="['size-1.5 shrink-0', isDestructive ? 'bg-destructive' : 'bg-foreground']" />
                <span>{{ item.name }}</span>
                <span v-if="item.quantity > 1" class="text-muted-foreground">×{{ item.quantity }}</span>
                <span class="truncate text-xs text-muted-foreground">· {{ item.reservation }}</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Rejection is only legal out of `created`; the rest of a mixed selection is skipped. -->
        <p v-if="decision === 'rejected' && hasUnrejectable" class="text-xs text-muted-foreground">
          {{ $t('reservations.bulk.reject_only_pending') }}
        </p>
        <p v-if="decision === 'resolved'" class="text-xs text-muted-foreground">
          {{ $t('reservations.bulk.resolve_hint') }}
        </p>
        <p v-if="decision === 'backtracked'" class="text-xs text-muted-foreground">
          {{ $t('reservations.bulk.backtrack_hint') }}
        </p>

        <div class="flex flex-col gap-2">
          <Label for="reservation-decision-notes">
            {{ $t('reservations.actions.notes') }}
            <span class="text-muted-foreground">({{ $t('reservations.actions.notes_optional') }})</span>
          </Label>
          <Textarea id="reservation-decision-notes" v-model="notes" rows="2" :placeholder="notesPlaceholder" />
        </div>

        <div class="flex justify-end gap-2">
          <Button variant="ghost" voice="sentence" class="pointer-coarse:h-11" @click="emit('update:open', false)">
            {{ $t('reservations.actions.cancel') }}
          </Button>
          <Button
            :variant="isDestructive ? 'destructive' : 'brand'"
            class="pointer-coarse:h-11"
            :disabled="processing || pivotIds.length === 0"
            @click="submit"
          >
            <component :is="icon" aria-hidden="true" />
            {{ confirmLabel }}
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Ban, Check, CheckCheck, Undo2, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import type { ReservationDecision } from './types';

import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
  getActionablePivotIds,
  getBacktrackAction,
  getCancellablePivotIds,
  getPrimaryAction,
  getRejectablePivotIds,
  type DashboardReservation,
  type ReservationResourceState,
} from '@/Utils/ReservationStatus';

const props = defineProps<{
  open: boolean;
  decision: ReservationDecision;
  targets: DashboardReservation[];
}>();

const emit = defineEmits<{
  'update:open': [open: boolean];
  /** The server accepted the decision; the caller refreshes its rows. */
  'done': [];
}>();

const notes = ref('');
const processing = ref(false);

watch(() => props.open, (open) => {
  if (open) {
    notes.value = '';
  }
});

/**
 * Which pivots the decision submits. Never reservation ids — the approval endpoint operates on
 * reservation_resource rows, and a selected reservation may hold items this user cannot touch.
 */
function pivotIdsFor(reservation: DashboardReservation): string[] {
  switch (props.decision) {
    case 'approved':
      return getPrimaryAction(reservation)?.pivotIds ?? [];
    case 'rejected':
      return getRejectablePivotIds(reservation);
    // Fully resolving takes every item still in flight, whatever step it is on.
    case 'resolved':
      return getActionablePivotIds(reservation);
    case 'backtracked':
      return getBacktrackAction(reservation)?.pivotIds ?? [];
    default:
      return getCancellablePivotIds(reservation);
  }
}

const pivotIds = computed(() => props.targets.flatMap(pivotIdsFor));

const hasUnrejectable = computed(() =>
  props.targets.some(reservation =>
    (getPrimaryAction(reservation)?.pivotIds.length ?? 0) > getRejectablePivotIds(reservation).length,
  ),
);

const TRANSITION_LABELS: Partial<Record<ReservationResourceState, string>> = {
  created: 'reservations.bulk.will_approve',
  reserved: 'reservations.bulk.will_hand_over',
  lent: 'reservations.bulk.will_return',
};

const BACKTRACK_LABELS: Partial<Record<ReservationResourceState, string>> = {
  reserved: 'reservations.bulk.will_backtrack_approval',
  lent: 'reservations.bulk.will_backtrack_hand_over',
  returned: 'reservations.bulk.will_backtrack_return',
};

const OUTCOME_LABELS: Record<string, string> = {
  rejected: 'reservations.bulk.will_reject',
  cancelled: 'reservations.bulk.will_cancel',
  resolved: 'reservations.bulk.will_resolve',
};

/** Group the affected items by the transition they trigger, so the dialog cannot mislead. */
const groups = computed(() => {
  const byKey = new Map<string, { key: string; name: string; quantity: number; reservation: string }[]>();
  const perState = props.decision === 'approved' || props.decision === 'backtracked';

  props.targets.forEach((reservation) => {
    const ids = pivotIdsFor(reservation);

    reservation.resources
      .filter(resource => ids.includes(String(resource.pivot.id)))
      .forEach((resource) => {
        // Approving advances each state differently, so group by state. The others are uniform.
        const key = perState ? resource.pivot.state : props.decision;
        const items = byKey.get(key) ?? [];

        items.push({
          key: String(resource.pivot.id),
          name: resource.name,
          quantity: resource.pivot.quantity,
          reservation: reservation.name,
        });
        byKey.set(key, items);
      });
  });

  return [...byKey.entries()].map(([state, items]) => {
    const labelKey = props.decision === 'approved'
      ? TRANSITION_LABELS[state as ReservationResourceState]
      : props.decision === 'backtracked'
        ? BACKTRACK_LABELS[state as ReservationResourceState]
        : OUTCOME_LABELS[props.decision];

    return { state, label: labelKey ? $t(labelKey) : '', items };
  });
});

const isDestructive = computed(() => props.decision === 'rejected' || props.decision === 'cancelled');

const title = computed(() => $t(`reservations.bulk.${{
  approved: 'approve_title',
  rejected: 'reject_title',
  cancelled: 'cancel_title',
  resolved: 'resolve_title',
  backtracked: 'backtrack_title',
}[props.decision]}`));

const description = computed(() =>
  $tChoice('reservations.bulk.affects', pivotIds.value.length, { count: pivotIds.value.length }),
);

const confirmLabel = computed(() => $t(`reservations.actions.${{
  approved: 'approve',
  rejected: 'reject',
  cancelled: 'cancel_reservation',
  resolved: 'resolve',
  backtracked: 'backtrack',
}[props.decision]}`));

const notesPlaceholder = computed(() => $t(`reservations.actions.${{
  approved: 'notes_placeholder',
  rejected: 'reject_notes_placeholder',
  cancelled: 'notes_placeholder',
  resolved: 'notes_placeholder',
  backtracked: 'backtrack_notes_placeholder',
}[props.decision]}`));

const icon = computed(() => ({
  approved: Check,
  resolved: CheckCheck,
  cancelled: Ban,
  backtracked: Undo2,
  rejected: X,
}[props.decision]));

function submit(): void {
  if (pivotIds.value.length === 0) {
    return;
  }

  processing.value = true;

  // Fully resolving and backtracking walk the state chain server-side; the rest are a single decision.
  const url = props.decision === 'resolved'
    ? route('approvals.resolve')
    : props.decision === 'backtracked'
      ? route('approvals.backtrack')
      : route('approvals.bulkStore');

  const payload: Record<string, unknown> = {
    approvable_type: 'reservation_resource',
    approvable_ids: pivotIds.value,
    notes: notes.value || null,
  };

  if (props.decision !== 'resolved' && props.decision !== 'backtracked') {
    payload.decision = props.decision;
    payload.step = 1;
  }

  router.post(url, payload, {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:open', false);
      emit('done');
    },
    onFinish: () => {
      processing.value = false;
    },
  });
}
</script>
