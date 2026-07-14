<template>
  <div class="relative">
    <ReservationBulkActionBar
      :count="selectedReservations.length"
      :can-reject="selectionHasRejectable"
      :disabled="processing"
      @approve="openDecision(selectedReservations, 'approved')"
      @reject="openDecision(selectedReservations, 'rejected')"
      @resolve="openDecision(selectedReservations, 'resolved')"
      @clear="clearSelection"
    />

    <DataTable
      :columns
      :data="reservations"
      :pagination="true"
      :page-size
      :initial-sort="INITIAL_SORT"
      :get-row-id="(row) => String(row.id)"
      :enable-row-selection="isAdministered ? canSelectRow : false"
      :enable-multi-row-selection="true"
      :enable-row-selection-column="isAdministered && reservations.some(isReservationSelectable)"
      :row-selection-state="rowSelection"
      :row-class-name="getRowClassName"
      :show-selection-count="false"
      :empty-message
      @update:row-selection="rowSelection = $event"
    >
      <!--
        An empty table under an active filter is not the same as having no reservations: say which
        it is, and offer the way out rather than leaving the user to work out what they hid.
      -->
      <template #empty>
        <div class="flex flex-col items-center justify-center gap-3 py-10 text-center">
          <p class="text-sm text-muted-foreground">
            {{ filtersApplied ? $t('reservations.dashboard.empty.filtered') : emptyMessage }}
          </p>
          <Button v-if="filtersApplied" variant="outline" size="sm" @click="$emit('clear-filters')">
            <X class="size-4" />
            {{ $t('reservations.dashboard.filters.clear') }}
          </Button>
        </div>
      </template>
    </DataTable>

    <!--
      Every decision — row-level or bulk — goes through this dialog. Approving advances each item
      one step along created → reserved → lent → returned, so the operator has to be able to see
      what their click is about to do before it happens, and to attach a note explaining why.
    -->
    <Dialog v-model:open="showDecisionDialog">
      <DialogContent class="max-w-lg">
        <DialogHeader>
          <DialogTitle>{{ decisionTitle }}</DialogTitle>
          <DialogDescription>{{ decisionDescription }}</DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
          <div
            :class="[
              'space-y-3 rounded-md border p-3 text-sm',
              isDestructive ? 'bg-destructive/5' : 'bg-muted/30',
            ]"
          >
            <div v-for="group in decisionGroups" :key="group.state" class="space-y-1">
              <div :class="['font-medium', isDestructive ? 'text-destructive' : 'text-muted-foreground']">
                {{ group.label }}
              </div>
              <ul class="ml-4 space-y-0.5">
                <li v-for="item in group.items" :key="item.key" class="flex items-center gap-2">
                  <span
                    :class="[
                      'size-1.5 shrink-0 rounded-full',
                      isDestructive ? 'bg-destructive' : 'bg-primary',
                    ]"
                  />
                  <span>{{ item.name }}</span>
                  <span v-if="item.quantity > 1" class="text-muted-foreground">×{{ item.quantity }}</span>
                  <span class="truncate text-xs text-muted-foreground">· {{ item.reservation }}</span>
                </li>
              </ul>
            </div>
          </div>

          <!-- Rejection is only legal out of `created`; the rest of a mixed selection is skipped. -->
          <p v-if="decision === 'rejected' && hasUnrejectableSelection" class="text-xs text-muted-foreground">
            {{ $t('reservations.bulk.reject_only_pending') }}
          </p>

          <!-- Fast-forwarding skips steps, so say so plainly before they commit. -->
          <p v-if="decision === 'resolved'" class="text-xs text-muted-foreground">
            {{ $t('reservations.bulk.resolve_hint') }}
          </p>

          <div class="space-y-2">
            <Label>
              {{ $t('reservations.actions.notes') }}
              <span class="text-muted-foreground">({{ $t('reservations.actions.notes_optional') }})</span>
            </Label>
            <Textarea
              v-model="notes"
              rows="2"
              :placeholder="decision === 'rejected'
                ? $t('reservations.actions.reject_notes_placeholder')
                : $t('reservations.actions.notes_placeholder')"
            />
          </div>

          <div class="flex justify-end gap-2">
            <Button variant="ghost" @click="showDecisionDialog = false">
              {{ $t('reservations.actions.cancel') }}
            </Button>
            <Button
              :variant="isDestructive ? 'destructive' : 'default'"
              :disabled="processing || decisionPivotIds.length === 0"
              @click="submitDecision"
            >
              <component :is="decisionIcon" class="size-4" />
              {{ decisionConfirmLabel }}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="tsx">
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef, RowSelectionState } from '@tanstack/vue-table';
import { Ban, Check, CheckCheck, Undo2, X } from 'lucide-vue-next';

import ReservationBulkActionBar from '@/Components/Tables/ReservationBulkActionBar.vue';
import ReservationPeriod from '@/Components/SmallElements/ReservationPeriod.vue';
import ReservationStateSummary from '@/Components/Tag/ReservationStateSummary.vue';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { DataTable } from '@/Components/ui/data-table';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import {
  getActionablePivotIds,
  getCancellablePivotIds,
  getPrimaryAction,
  getRejectablePivotIds,
  getReservationStates,
  isReservationSelectable,
  isReservationUnresolved,
  type DashboardReservation,
  type ReservationResourceState,
} from '@/Utils/ReservationStatus';

const props = withDefaults(defineProps<{
  reservations: DashboardReservation[];
  /** `administered` adds approval actions and selection; `mine` is the requester's own view. */
  mode: 'mine' | 'administered';
  emptyMessage?: string;
  /** Changes the empty state from "nothing here" to "nothing matches", with a way to clear. */
  filtersApplied?: boolean;
  pageSize?: number;
}>(), {
  pageSize: 10,
  filtersApplied: false,
});

defineEmits<{
  'clear-filters': [];
}>();

/** `resolved` is not a server decision — it fast-forwards items to their final approved state. */
type Decision = 'approved' | 'rejected' | 'cancelled' | 'resolved';

/** Beyond this, the chips roll up into a "+N" with the rest on hover. */
const VISIBLE_RESOURCE_CHIPS = 4;

/**
 * Newest first by default, but driven through the table's own sorting rather than pre-sorting the
 * array — that way the Period header carries a sort indicator and the user can re-sort it.
 */
const INITIAL_SORT = [{ id: 'start_time', desc: true }];

const isAdministered = computed(() => props.mode === 'administered');

const rowSelection = ref<RowSelectionState>({});
const processing = ref(false);
const notes = ref('');
const showDecisionDialog = ref(false);
const decision = ref<Decision>('approved');
const decisionTargets = ref<DashboardReservation[]>([]);

const clearSelection = () => {
  rowSelection.value = {};
};

const canSelectRow = (row: { original: DashboardReservation }) => isReservationSelectable(row.original);

const selectedReservations = computed(() =>
  Object.keys(rowSelection.value)
    .filter(id => rowSelection.value[id])
    .map(id => props.reservations.find(reservation => String(reservation.id) === id))
    .filter((reservation): reservation is DashboardReservation => reservation !== undefined),
);

const selectionHasRejectable = computed(() =>
  selectedReservations.value.some(reservation => getRejectablePivotIds(reservation).length > 0),
);

const openDecision = (targets: DashboardReservation[], next: Decision) => {
  decisionTargets.value = targets;
  decision.value = next;
  notes.value = '';
  showDecisionDialog.value = true;
};

/**
 * Which pivots the decision will submit. Never reservation IDs — the approval endpoint operates on
 * reservation_resource rows, and a selected reservation may hold items this user cannot touch.
 */
const pivotIdsFor = (reservation: DashboardReservation): string[] => {
  if (decision.value === 'approved') {
    return getPrimaryAction(reservation)?.pivotIds ?? [];
  }
  if (decision.value === 'rejected') {
    return getRejectablePivotIds(reservation);
  }
  // Fully resolving takes every item still in flight, whatever step it is on.
  if (decision.value === 'resolved') {
    return getActionablePivotIds(reservation);
  }

  return getCancellablePivotIds(reservation);
};

const decisionPivotIds = computed(() => decisionTargets.value.flatMap(pivotIdsFor));

const hasUnrejectableSelection = computed(() =>
  decisionTargets.value.some(reservation =>
    (getPrimaryAction(reservation)?.pivotIds.length ?? 0) > getRejectablePivotIds(reservation).length,
  ),
);

const TRANSITION_LABELS: Record<ReservationResourceState, string> = {
  created: 'reservations.bulk.will_approve',
  reserved: 'reservations.bulk.will_hand_over',
  lent: 'reservations.bulk.will_return',
  returned: '',
  rejected: '',
  cancelled: '',
};

interface DecisionItem { key: string; name: string; quantity: number; reservation: string }

/** Group the affected items by the transition they trigger, so the dialog cannot mislead. */
const decisionGroups = computed(() => {
  const groups = new Map<string, DecisionItem[]>();

  decisionTargets.value.forEach((reservation) => {
    const pivotIds = pivotIdsFor(reservation);

    reservation.resources
      .filter(resource => pivotIds.includes(String(resource.pivot.id)))
      .forEach((resource) => {
        // Approving advances each state differently, so group by state. The others are uniform.
        const key = decision.value === 'approved'
          ? resource.pivot.state
          : decision.value;

        const items = groups.get(key) ?? [];

        items.push({
          key: String(resource.pivot.id),
          name: resource.name,
          quantity: resource.pivot.quantity,
          reservation: reservation.name,
        });

        groups.set(key, items);
      });
  });

  const OUTCOME_LABELS: Record<string, string> = {
    rejected: 'reservations.bulk.will_reject',
    cancelled: 'reservations.bulk.will_cancel',
    resolved: 'reservations.bulk.will_resolve',
  };

  return [...groups.entries()].map(([state, items]) => ({
    state,
    label: decision.value === 'approved'
      ? $t(TRANSITION_LABELS[state as ReservationResourceState] ?? '')
      : $t(OUTCOME_LABELS[decision.value] ?? ''),
    items,
  }));
});

const decisionTitle = computed(() => {
  if (decision.value === 'rejected') {
    return $t('reservations.bulk.reject_title');
  }
  if (decision.value === 'cancelled') {
    return $t('reservations.bulk.cancel_title');
  }
  if (decision.value === 'resolved') {
    return $t('reservations.bulk.resolve_title');
  }

  return $t('reservations.bulk.approve_title');
});

const decisionDescription = computed(() =>
  $tChoice('reservations.bulk.affects', decisionPivotIds.value.length, {
    count: decisionPivotIds.value.length,
  }),
);

const decisionConfirmLabel = computed(() => {
  if (decision.value === 'rejected') {
    return $t('reservations.actions.reject');
  }
  if (decision.value === 'cancelled') {
    return $t('reservations.actions.cancel_reservation');
  }
  if (decision.value === 'resolved') {
    return $t('reservations.actions.resolve');
  }

  return $t('reservations.actions.approve');
});

const decisionIcon = computed(() => {
  switch (decision.value) {
    case 'approved':
      return Check;
    case 'resolved':
      return CheckCheck;
    case 'cancelled':
      return Ban;
    default:
      return X;
  }
});

const isDestructive = computed(() => decision.value === 'rejected' || decision.value === 'cancelled');

const submitDecision = () => {
  if (decisionPivotIds.value.length === 0) {
    return;
  }

  processing.value = true;

  // Fully resolving walks the state chain server-side; the others are a single decision.
  const url = decision.value === 'resolved'
    ? route('approvals.resolve')
    : route('approvals.bulkStore');

  const payload: Record<string, unknown> = {
    approvable_type: 'reservation_resource',
    approvable_ids: decisionPivotIds.value,
    notes: notes.value || null,
  };

  if (decision.value !== 'resolved') {
    payload.decision = decision.value;
    payload.step = 1;
  }

  router.post(url, payload, {
    preserveScroll: true,
    onSuccess: () => {
      notes.value = '';
      showDecisionDialog.value = false;
      decisionTargets.value = [];
      clearSelection();
    },
    onFinish: () => {
      processing.value = false;
    },
  });
};

const rowStates = (reservation: DashboardReservation) =>
  getReservationStates(reservation, { approvableOnly: isAdministered.value });

const getRowClassName = (_reservation: DashboardReservation) => '';

const ACTION_LABELS: Record<string, string> = {
  created: 'reservations.actions.approve',
  reserved: 'reservations.actions.hand_over',
  lent: 'reservations.actions.mark_returned',
};

const columns = computed<ColumnDef<DashboardReservation, any>[]>(() => [
  {
    accessorKey: 'name',
    header: () => $t('reservations.dashboard.columns.reservation'),
    size: 220,
    cell: ({ row }) => {
      const reservation = row.original;
      const managers = reservation.users?.length ?? 0;

      return (
        <div class="min-w-0">
          <Link
            href={route('reservations.show', { reservation: reservation.id })}
            class="block truncate font-medium hover:underline"
          >
            {reservation.name}
          </Link>
          {managers > 0 && (
            <span class="text-xs text-muted-foreground">
              {$tChoice('reservations.dashboard.managers', managers, { count: managers })}
            </span>
          )}
        </div>
      );
    },
  },
  {
    id: 'requester',
    header: () => $t('reservations.dashboard.columns.requester'),
    size: 160,
    cell: ({ row }) => {
      const { users } = row.original;

      if (!users?.length) {
        return <span class="text-sm text-muted-foreground">—</span>;
      }

      return (
        <div class="flex items-center gap-2">
          <UsersAvatarGroup users={users} size={24} max={2} />
          <span class="max-w-[120px] truncate text-sm">{users[0].name}</span>
        </div>
      );
    },
  },
  {
    id: 'resources',
    header: () => $t('reservations.dashboard.columns.resources'),
    size: 260,
    cell: ({ row }) => {
      // Some reservations carry 25+ items. Rendering them all turns one row into a whole screen,
      // so show a few and roll the rest into a "+N" chip that lists them on hover.
      const all = row.original.resources;
      const shown = all.slice(0, VISIBLE_RESOURCE_CHIPS);
      const hidden = all.slice(VISIBLE_RESOURCE_CHIPS);

      return (
        <TooltipProvider>
          <div class="flex flex-wrap items-center gap-1">
            {shown.map((resource) => {
              // Foreign items stay visible — they explain why an approval covers only part of a
              // row — but are muted so it is obvious they are not this administrator's to act on.
              const isMine = !isAdministered.value || resource.pivot.approvable;

              const chip = (
                <Badge
                  variant={isMine ? 'secondary' : 'outline'}
                  size="tiny"
                  class={isMine ? '' : 'text-muted-foreground opacity-60'}
                >
                  <span class="max-w-[110px] truncate">{resource.name}</span>
                  {resource.pivot.quantity > 1 && (
                    <span class="text-muted-foreground">
                      ×
                      {resource.pivot.quantity}
                    </span>
                  )}
                </Badge>
              );

              if (isMine) {
                return <div key={resource.pivot.id}>{chip}</div>;
              }

              return (
                <Tooltip key={resource.pivot.id}>
                  <TooltipTrigger as-child>
                    <div class="cursor-help">{chip}</div>
                  </TooltipTrigger>
                  <TooltipContent>
                    {$t('reservations.dashboard.not_yours', {
                      tenant: resource.tenant?.shortname ?? '—',
                    })}
                  </TooltipContent>
                </Tooltip>
              );
            })}

            {hidden.length > 0 && (
              <Tooltip>
                <TooltipTrigger as-child>
                  <div class="cursor-help">
                    <Badge variant="outline" size="tiny" class="text-muted-foreground">
                      +
                      {hidden.length}
                    </Badge>
                  </div>
                </TooltipTrigger>
                <TooltipContent class="max-w-xs">
                  <div class="flex flex-col gap-0.5">
                    {hidden.map(resource => (
                      <span key={resource.pivot.id}>
                        {resource.name}
                        {resource.pivot.quantity > 1 ? ` ×${resource.pivot.quantity}` : ''}
                      </span>
                    ))}
                  </div>
                </TooltipContent>
              </Tooltip>
            )}
          </div>
        </TooltipProvider>
      );
    },
  },
  {
    accessorKey: 'start_time',
    header: () => $t('reservations.dashboard.columns.period'),
    size: 120,
    enableSorting: true,
    cell: ({ row }) => (
      <ReservationPeriod
        startTime={row.original.start_time}
        endTime={row.original.end_time}
      />
    ),
  },
  {
    id: 'status',
    header: () => $t('reservations.dashboard.columns.status'),
    size: 130,
    cell: ({ row }) => {
      // A reservation's items can sit in several states at once, so the badge reports all of them.
      // How late it is lives in the period cell; the badge only carries the states.
      return (
        <ReservationStateSummary
          states={rowStates(row.original)}
          unresolved={isReservationUnresolved(row.original, { approvableOnly: isAdministered.value })}
        />
      );
    },
  },
  {
    id: 'actions',
    header: () => $t('reservations.dashboard.columns.actions'),
    size: 180,
    cell: ({ row }) => {
      const reservation = row.original;

      if (!isAdministered.value) {
        if (getCancellablePivotIds(reservation).length === 0) {
          return null;
        }

        return (
          <Button
            size="sm"
            variant="ghost"
            class="text-muted-foreground"
            disabled={processing.value}
            onClick={() => openDecision([reservation], 'cancelled')}
          >
            <Ban class="size-4" />
            {$t('reservations.actions.cancel_reservation')}
          </Button>
        );
      }

      const action = getPrimaryAction(reservation);

      if (!action) {
        return null;
      }

      return (
        <div class="flex items-center gap-1">
          <Button
            size="sm"
            variant={action.state === 'lent' ? 'outline' : 'default'}
            disabled={processing.value}
            onClick={() => openDecision([reservation], 'approved')}
          >
            {action.state === 'lent' ? <Undo2 class="size-4" /> : <Check class="size-4" />}
            {$t(ACTION_LABELS[action.state])}
          </Button>

          {/* Rejection is only a legal transition out of `created`. */}
          {action.state === 'created' && (
            <Button
              size="icon-sm"
              variant="ghost"
              class="text-destructive hover:text-destructive"
              disabled={processing.value}
              title={$t('reservations.actions.reject')}
              onClick={() => openDecision([reservation], 'rejected')}
            >
              <X class="size-4" />
            </Button>
          )}
        </div>
      );
    },
  },
]);
</script>
