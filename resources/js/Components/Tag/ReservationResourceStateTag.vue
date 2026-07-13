<template>
  <Badge :variant="badgeVariant" class="gap-1">
    <component :is="icon" class="size-3 shrink-0" />
    <span>{{ capitalize($t(`state.status.${displayState}`)) }}</span>
    <TriangleAlert
      v-if="unresolved"
      :title="$t('reservations.unresolved_help')"
      class="size-3 shrink-0 text-amber-600 dark:text-amber-400"
    />
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
import type { ReservationResourceState } from '@/Utils/ReservationStatus';

const props = defineProps<{
  state: ReservationResourceState;
  unresolved?: boolean;
  state_properties?: App.Entities.ReservationResource['state_properties'];
}>();

const displayState = computed<ReservationResourceState>(() => props.state ?? 'created');

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
    case 'returned':
      return 'success';
    // Cancelling is the requester calling it off, not a failure — it should not read as alarming.
    case 'cancelled':
      return 'secondary';
    case 'rejected':
      return 'rose';
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
