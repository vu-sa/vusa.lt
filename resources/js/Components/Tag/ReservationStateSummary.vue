<template>
  <span v-if="states.length === 0" class="text-muted-foreground">—</span>

  <!-- One state: nothing to summarise, so show the item tag itself. -->
  <ReservationResourceStateTag
    v-else-if="states.length === 1"
    :state="states[0].state"
    :unresolved
  />

  <HoverCard v-else>
    <HoverCardTrigger as-child>
      <Badge variant="secondary" class="cursor-default gap-1.5">
        <span class="flex shrink-0 -space-x-1">
          <span
            v-for="{ state } in states"
            :key="state"
            :class="['size-2 rounded-full ring-1 ring-background', getStatusDotClass(state)]"
          />
        </span>
        <span>{{ $t('reservations.status_mixed') }}</span>
        <TriangleAlert
          v-if="unresolved"
          :title="$t('reservations.unresolved_help')"
          class="size-3 shrink-0 text-amber-600 dark:text-amber-400"
        />
      </Badge>
    </HoverCardTrigger>
    <HoverCardContent class="w-auto min-w-48 p-3">
      <ul class="flex flex-col gap-1.5">
        <li
          v-for="{ state, count } in states"
          :key="state"
          class="flex items-center justify-between gap-4 text-sm"
        >
          <span class="flex items-center gap-2">
            <span :class="['size-2 shrink-0 rounded-full', getStatusDotClass(state)]" />
            {{ capitalize($t(`state.status.${state}`)) }}
          </span>
          <span class="tabular-nums text-muted-foreground">{{ count }}</span>
        </li>
      </ul>
    </HoverCardContent>
  </HoverCard>
</template>

<script setup lang="ts">
import { TriangleAlert } from 'lucide-vue-next';

import ReservationResourceStateTag from './ReservationResourceStateTag.vue';

import { Badge } from '@/Components/ui/badge';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/Components/ui/hover-card';
import { capitalize } from '@/Utils/String';
import type { ReservationStateCount } from '@/Utils/ReservationStatus';
import { getStatusDotClass } from '@/Utils/ReservationStatus';

/**
 * A reservation's overall state. Its items can sit in several states at once, so more than one
 * collapses into a "mixed" badge whose dots and hover breakdown say which ones, and how many
 * items are in each — rather than picking one state and hiding the rest.
 */
defineProps<{
  states: ReservationStateCount[];
  /** Any of the items is past its reserved window and still open. */
  unresolved?: boolean;
}>();
</script>
