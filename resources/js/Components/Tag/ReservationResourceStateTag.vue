<template>
  <Badge :variant="badgeVariant" class="gap-1">
    <component :is="icon" class="size-3 shrink-0" />
    <span>{{ capitalize($t(`state.status.${displayState}`)) }}</span>
    <InfoPopover v-if="state_properties">
      {{ state_properties.description }}
    </InfoPopover>
  </Badge>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
  Ban,
  BookmarkCheck,
  CircleCheck,
  CircleX,
  Clock,
  PackageOpen,
  TriangleAlert,
} from 'lucide-vue-next';

import InfoPopover from '../Buttons/InfoPopover.vue';

import type { BadgeVariants } from '@/Components/ui/badge';
import { Badge } from '@/Components/ui/badge';
import { capitalize } from '@/Utils/String';
import type { ReservationRowStatus } from '@/Utils/ReservationStatus';

const props = defineProps<{
  state: App.Entities.ReservationResource['state'];
  state_properties?: App.Entities.ReservationResource['state_properties'];
  /**
   * A lent resource whose return time has passed. Not a stored state — callers derive it with
   * `isPivotOverdue` / `getReservationRowStatus`, and it takes over the badge when set.
   */
  overdue?: boolean;
}>();

const displayState = computed<ReservationRowStatus>(
  () => (props.overdue ? 'overdue' : props.state ?? 'created') as ReservationRowStatus,
);

/**
 * Every state gets its own colour. `lent` and `returned` previously shared one, which made an item
 * still out on loan indistinguishable from one already back on the shelf.
 */
const badgeVariant = computed<BadgeVariants['variant']>(() => {
  switch (displayState.value) {
    case 'created':
      return 'warning';
    case 'reserved':
      return 'outline';
    case 'lent':
      return 'default';
    case 'overdue':
    case 'rejected':
      return 'rose';
    case 'returned':
      return 'success';
    // Cancelling is the requester calling it off, not a failure — it should not read as alarming.
    case 'cancelled':
      return 'secondary';
    default:
      return 'secondary';
  }
});

const icon = computed(() => {
  switch (displayState.value) {
    case 'created':
      return Clock;
    case 'reserved':
      return BookmarkCheck;
    case 'lent':
      return PackageOpen;
    case 'overdue':
      return TriangleAlert;
    case 'returned':
      return CircleCheck;
    case 'rejected':
      return CircleX;
    case 'cancelled':
      return Ban;
    default:
      return Clock;
  }
});
</script>
