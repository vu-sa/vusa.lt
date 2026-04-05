<template>
  <Badge :variant="badgeVariant" class="gap-1">
    <span>{{ capitalize($t(`state.status.${state}`)) }}</span>
    <InfoPopover v-if="state_properties">
      {{ state_properties.description }}
    </InfoPopover>
  </Badge>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import InfoPopover from '../Buttons/InfoPopover.vue';

import { Badge } from '@/Components/ui/badge';
import { capitalize } from '@/Utils/String';

const props = defineProps<{
  state: App.Entities.ReservationResource['state'];
  state_properties?: App.Entities.ReservationResource['state_properties'];
}>();

// Map state to Shadcn badge variant
const badgeVariant = computed<'default' | 'secondary' | 'destructive' | 'outline'>(() => {
  switch (props.state) {
    case 'lent':
    case 'returned':
      return 'default';
    case 'rejected':
    case 'cancelled':
      return 'destructive';
    case 'created':
    case 'reserved':
      return 'outline';
    default:
      return 'secondary';
  }
});
</script>
