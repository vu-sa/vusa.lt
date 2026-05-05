<template>
  <!-- Visual state machine progress indicator -->
  <div class="flex items-center gap-1">
    <template v-for="(step, index) in steps" :key="step.state">
      <!-- Step indicator -->
      <div class="flex flex-col items-center gap-1">
        <div
          :class="[
            'flex size-6 items-center justify-center rounded-full text-xs font-medium transition-all',
            getStepClasses(step.state)
          ]"
        >
          <component
            :is="IFluentCheckmark12Filled"
            v-if="getStepStatus(step.state) === 'completed'"
            class="size-3.5"
          />
          <span v-else>{{ index + 1 }}</span>
        </div>
        <span
          :class="[
            'text-[10px] leading-tight',
            getStepStatus(step.state) === 'current'
              ? 'font-medium text-foreground'
              : 'text-muted-foreground'
          ]"
        >
          {{ $t(`state.status.${step.state}`) }}
        </span>
      </div>

      <!-- Connector line -->
      <div
        v-if="index < steps.length - 1"
        :class="[
          'mb-4 h-0.5 w-4 transition-colors',
          isStepCompleted(step.state) ? 'bg-primary' : 'bg-zinc-200 dark:bg-zinc-700'
        ]"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import IFluentCheckmark12Filled from '~icons/fluent/checkmark-12-filled';

type ReservationState = 'created' | 'reserved' | 'lent' | 'returned' | 'rejected' | 'cancelled';

const props = defineProps<{
  currentState?: ReservationState;
}>();

const steps = [
  { state: 'created' as const },
  { state: 'reserved' as const },
  { state: 'lent' as const },
  { state: 'returned' as const },
];

const stateOrder: Record<ReservationState, number> = {
  created: 0,
  reserved: 1,
  lent: 2,
  returned: 3,
  rejected: -1,
  cancelled: -1,
};

const currentOrder = computed(() => stateOrder[props.currentState ?? 'created'] ?? 0);

const isRejectedOrCancelled = computed(() =>
  props.currentState === 'rejected' || props.currentState === 'cancelled',
);

const getStepStatus = (state: ReservationState): 'completed' | 'current' | 'upcoming' => {
  const stepOrder = stateOrder[state];

  if (isRejectedOrCancelled.value) {
    return 'upcoming'; // Grey out everything
  }

  if (stepOrder < currentOrder.value) return 'completed';
  if (stepOrder === currentOrder.value) return 'current';
  return 'upcoming';
};

const isStepCompleted = (state: ReservationState) => {
  return getStepStatus(state) === 'completed';
};

const getStepClasses = (state: ReservationState) => {
  const status = getStepStatus(state);

  if (isRejectedOrCancelled.value) {
    return 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 opacity-50';
  }

  switch (status) {
    case 'completed':
      return 'bg-primary text-primary-foreground';
    case 'current':
      return 'bg-primary/20 text-primary ring-2 ring-primary ring-offset-1 dark:ring-offset-zinc-900';
    case 'upcoming':
      return 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500';
  }
};
</script>
