<template>
  <div class="flex shrink-0 items-center gap-1" data-slot="reservation-row-actions">
    <Button
      v-if="primary"
      size="sm"
      :variant="primary.state === 'lent' ? 'outline' : 'brand'"
      voice="sentence"
      class="pointer-coarse:h-11"
      :disabled
      @click="emit('decide', 'approved', reservation)"
    >
      <Check aria-hidden="true" />
      {{ $t(PRIMARY_LABELS[primary.state] ?? 'reservations.actions.approve') }}
    </Button>

    <DropdownMenu v-if="hasMoreActions">
      <DropdownMenuTrigger as-child>
        <Button
          size="icon-sm"
          variant="ghost"
          class="pointer-coarse:size-11"
          :disabled
          :title="$t('Veiksmai')"
          :aria-label="$t('Veiksmai')"
        >
          <Ellipsis aria-hidden="true" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" class="min-w-44">
        <DropdownMenuItem
          v-if="primary?.state === 'created'"
          variant="destructive"
          class="pointer-coarse:min-h-11"
          @select="emit('decide', 'rejected', reservation)"
        >
          <X aria-hidden="true" />
          {{ $t('reservations.actions.reject') }}
        </DropdownMenuItem>
        <DropdownMenuItem v-if="backtrack" class="pointer-coarse:min-h-11" @select="onBacktrack">
          <Undo2 aria-hidden="true" />
          {{ $t('reservations.actions.backtrack') }}
        </DropdownMenuItem>
        <DropdownMenuItem v-if="cancellable" class="pointer-coarse:min-h-11" @select="emit('decide', 'cancelled', reservation)">
          <Ban aria-hidden="true" />
          {{ $t('reservations.actions.cancel_reservation') }}
        </DropdownMenuItem>
        <slot name="more-actions" />
      </DropdownMenuContent>
    </DropdownMenu>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Ban, Check, Ellipsis, Undo2, X } from 'lucide-vue-next';
import { computed } from 'vue';

import type { ReservationDecision } from './types';

import { Button } from '@/Components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import {
  getBacktrackAction,
  getCancellablePivotIds,
  getPrimaryAction,
  type DashboardReservation,
} from '@/Utils/ReservationStatus';

const props = defineProps<{
  reservation: DashboardReservation;
  disabled?: boolean;
  extraActions?: boolean;
}>();

const emit = defineEmits<{
  decide: [decision: ReservationDecision, reservation: DashboardReservation];
}>();

function onBacktrack(): void {
  emit('decide', 'backtracked', props.reservation);
}

const PRIMARY_LABELS: Record<string, string> = {
  created: 'reservations.actions.approve',
  reserved: 'reservations.actions.hand_over',
  lent: 'reservations.actions.mark_returned',
};

// Approving, rejecting and backtracking act only on items the server marked `approvable`; the
// requester's own cancel is independent, so a manager who booked something can still call it off.
const primary = computed(() => getPrimaryAction(props.reservation));
const backtrack = computed(() => getBacktrackAction(props.reservation));
const cancellable = computed(() => getCancellablePivotIds(props.reservation).length > 0);
const hasMoreActions = computed(() => primary.value?.state === 'created' || Boolean(backtrack.value) || cancellable.value || props.extraActions);
</script>
