<template>
  <div data-slot="reservation-resource-list">
    <EmptyState
      v-if="resources.length === 0"
      :title="$t('Ištekliai dar nepridėti')"
      :description="$t('Pridėk išteklių, kuriuos šiai rezervacijai reikia paimti.')"
      :icon="ResourceIcon"
      :action-label="canEdit ? $t('Pridėti išteklių') : undefined"
      @action="emit('add-resource')"
    />

    <ul v-else class="divide-y divide-border border-y border-border">
      <li v-for="resource in resources" :key="resource.pivot.id" class="py-3" data-slot="reservation-resource-row">
        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2 px-2 sm:px-3">
          <div class="min-w-0 flex-1 space-y-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
              <Link
                :href="route('resources.show', resource.id)"
                class="truncate text-sm font-medium text-foreground hover:text-brand hover:underline"
              >
                {{ resource.name }}
              </Link>
              <span v-if="resource.pivot.quantity > 1" class="text-sm text-muted-foreground tabular-nums">×{{ resource.pivot.quantity }}</span>
              <span
                v-if="resource.tenant?.shortname"
                class="border border-border bg-secondary px-1.5 py-0.5 text-xs font-medium text-muted-foreground"
              >
                {{ resource.tenant.shortname }}
              </span>
              <StatusBadge :status="statusOf(resource)" />
            </div>

            <p class="text-xs text-muted-foreground tabular-nums">
              <ReservationPeriod :start-time="resource.pivot.start_time" :end-time="resource.pivot.end_time" />
            </p>

            <p v-if="isOverbooked(resource)" class="text-xs text-status-danger" data-testid="overbooked">
              {{ $t('Trūksta išteklių šiam laikotarpiui') }}
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-1">
            <ReservationRowActions
              :reservation="rowTarget(resource)"
              :extra-actions="canEdit && isEditable(resource)"
              @decide="onDecide"
            >
              <template v-if="canEdit && isEditable(resource)" #more-actions>
                <DropdownMenuItem class="pointer-coarse:min-h-11" :aria-label="$t('Redaguoti')" @select="emit('edit', resource)">
                  <Pencil aria-hidden="true" />
                  {{ $t('Redaguoti') }}
                </DropdownMenuItem>
                <DropdownMenuItem variant="destructive" class="pointer-coarse:min-h-11" :aria-label="$t('Pašalinti')" @select="askRemove(resource)">
                  <Trash2 aria-hidden="true" />
                  {{ $t('Pašalinti') }}
                </DropdownMenuItem>
              </template>
            </ReservationRowActions>
          </div>
        </div>

        <!-- Who decided what, and whether it was later undone. Nothing to say → nothing to open. -->
        <details v-if="approvalsOf(resource).length > 0" class="group mt-2 px-2 sm:px-3">
          <summary class="flex cursor-pointer select-none items-center gap-1 text-xs text-muted-foreground pointer-coarse:min-h-11 pointer-coarse:items-center">
            <ChevronRight class="size-3.5 transition-transform group-open:rotate-90" aria-hidden="true" />
            {{ $t('Sprendimų istorija') }}
            <span class="tabular-nums">({{ approvalsOf(resource).length }})</span>
          </summary>
          <ul class="mt-1.5 space-y-1 border-l border-border pl-3">
            <li v-for="approval in approvalsOf(resource)" :key="approval.id" class="text-xs">
              <span :class="approval.reverted_at ? 'text-muted-foreground line-through' : 'text-foreground'">
                {{ decisionLabel(approval.decision) }} — {{ approval.user?.name ?? $t('Nežinomas') }}
              </span>
              <span class="ml-1 text-muted-foreground tabular-nums">{{ formatDateTime(approval.created_at) }}</span>
            </li>
          </ul>
        </details>
      </li>
    </ul>

    <ReservationDecisionDialog
      v-model:open="decisionOpen"
      :decision
      :targets="decisionTargets"
      @done="emit('changed')"
    />

    <ConfirmDialog
      v-model:open="removeOpen"
      :title="$t('Pašalinti išteklių?')"
      :description="$t('Išteklius bus pašalintas iš šios rezervacijos.')"
      :confirm-label="$t('Pašalinti')"
      destructive
      @confirm="remove"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, Pencil, Trash2 } from 'lucide-vue-next';

import ReservationDecisionDialog from './ReservationDecisionDialog.vue';
import ReservationRowActions from './ReservationRowActions.vue';
import type { ReservationDecision } from './types';

import { ResourceIcon } from '@/Components/icons';
import { ConfirmDialog, EmptyState, StatusBadge } from '@/Components/Patterns';
import ReservationPeriod from '@/Components/SmallElements/ReservationPeriod.vue';
import { Button } from '@/Components/ui/button';
import { DropdownMenuItem } from '@/Components/ui/dropdown-menu';
import { reservationResourceStatuses, type ReservationResourceStatus } from '@/Constants/statuses';
import type { DashboardReservation } from '@/Utils/ReservationStatus';
import { formatDateTime } from '@/Utils/dateTime';

interface ApprovalRow {
  id: string;
  decision: string;
  reverted_at?: string | null;
  created_at: string;
  user?: { name: string } | null;
}

/** A resource of the record payload: the pivot carries the approvals and the capacity check. */
type RecordResource = DashboardReservation['resources'][number] & {
  pivot: { approvals?: ApprovalRow[] };
  lowestCapacityAtDateTimeRange?: number | null;
  /** `resources.update` on this resource: its own page would be a 403 for anyone else. */
  can_edit?: boolean;
};

const props = defineProps<{
  /** The rows to show — the record's resources, already narrowed by any unit filter. */
  resources: RecordResource[];
  /**
   * The same reservation through `SerializeReservationsForTable`, the one source of the
   * approvable / backtrackable / cancellable flags — so a row never offers what the server refuses.
   */
  target: DashboardReservation;
  /** `reservations.update` on this record: changing or removing an item. */
  canEdit?: boolean;
}>();

const emit = defineEmits<{
  'add-resource': [];
  'edit': [resource: RecordResource];
  /** A decision or removal went through; the page refreshes its props. */
  'changed': [];
}>();

const statusOf = (resource: RecordResource) =>
  reservationResourceStatuses[resource.pivot.state as ReservationResourceStatus];

/** Only an item still in flight can be changed; the rest is history. */
const isEditable = (resource: RecordResource) => ['created', 'reserved'].includes(resource.pivot.state);

const isOverbooked = (resource: RecordResource) =>
  isEditable(resource) && (resource.lowestCapacityAtDateTimeRange ?? 0) < 0;

const approvalsOf = (resource: RecordResource) => resource.pivot.approvals ?? [];

const decisionLabel = (value: string) => $t(`reservations.decision.${value}`);

/** One row = the target reduced to that item, so the shared row actions and dialog act on it alone. */
const rowTarget = (resource: RecordResource): DashboardReservation => {
  const flagged = props.target.resources.find(item => String(item.pivot.id) === String(resource.pivot.id));

  return { ...props.target, resources: flagged ? [flagged] : [] };
};

const decision = ref<ReservationDecision>('approved');
const decisionOpen = ref(false);
const decisionTargets = ref<DashboardReservation[]>([]);

const onDecide = (next: ReservationDecision, reservation: DashboardReservation) => {
  decision.value = next;
  decisionTargets.value = [reservation];
  decisionOpen.value = true;
};

const removeOpen = ref(false);
const removing = ref<RecordResource | null>(null);

const askRemove = (resource: RecordResource) => {
  removing.value = resource;
  removeOpen.value = true;
};

const remove = () => {
  if (!removing.value) {
    return;
  }

  router.delete(route('reservationResources.destroy', { reservationResource: removing.value.pivot.id }), {
    preserveScroll: true,
    onSuccess: () => emit('changed'),
  });
};
</script>
