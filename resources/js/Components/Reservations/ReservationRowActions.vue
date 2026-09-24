<template>
  <div class="flex items-center gap-1" data-slot="reservation-row-actions">
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

    <!-- Rejection is only a legal transition out of `created`. -->
    <Button
      v-if="primary?.state === 'created'"
      size="icon-sm"
      variant="ghost"
      class="text-destructive hover:text-destructive pointer-coarse:size-11"
      :disabled
      :title="$t('reservations.actions.reject')"
      :aria-label="$t('reservations.actions.reject')"
      @click="emit('decide', 'rejected', reservation)"
    >
      <X aria-hidden="true" />
    </Button>

    <component
      :is="spotlight ? SpotlightPopover : 'div'"
      v-if="backtrack"
      v-bind="spotlight ? spotlightProps : {}"
    >
      <Button
        size="icon-sm"
        variant="ghost"
        class="pointer-coarse:size-11"
        :disabled
        :title="$t('reservations.actions.backtrack')"
        :aria-label="$t('reservations.actions.backtrack')"
        @click="onBacktrack"
      >
        <Undo2 aria-hidden="true" />
      </Button>
    </component>

    <Button
      v-if="cancellable"
      size="sm"
      variant="ghost"
      voice="sentence"
      class="text-muted-foreground pointer-coarse:h-11"
      :disabled
      @click="emit('decide', 'cancelled', reservation)"
    >
      <Ban aria-hidden="true" />
      {{ $t('reservations.actions.cancel_reservation') }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Ban, Check, Undo2, X } from 'lucide-vue-next';
import { computed } from 'vue';

import type { ReservationDecision } from './types';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { Button } from '@/Components/ui/button';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import {
  getBacktrackAction,
  getCancellablePivotIds,
  getPrimaryAction,
  type DashboardReservation,
} from '@/Utils/ReservationStatus';

const props = defineProps<{
  reservation: DashboardReservation;
  disabled?: boolean;
  /** Draws the one-off "you can undo this" hint on the backtrack button; only one row per page sets it. */
  spotlight?: boolean;
}>();

const emit = defineEmits<{
  decide: [decision: ReservationDecision, reservation: DashboardReservation];
}>();

const backtrackSpotlight = useFeatureSpotlight('reservation-approval-backtrack-v1');

const spotlightProps = computed(() => ({
  title: $t('reservations.spotlight.backtrack_title'),
  description: $t('reservations.spotlight.backtrack_description'),
  position: 'left',
  isDismissed: backtrackSpotlight.isDismissed.value,
  onDismiss: backtrackSpotlight.dismiss,
}));

function onBacktrack(): void {
  void backtrackSpotlight.dismiss();
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
</script>
