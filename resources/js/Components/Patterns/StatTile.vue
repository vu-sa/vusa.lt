<template>
  <div :class="cn('flex items-end gap-4', props.class)" data-slot="stat-tile">
    <div class="flex items-baseline gap-1">
      <span
        :class="['text-4xl font-bold tabular-nums', valueClass]"
        :aria-label
      >{{ value }}</span>
      <span v-if="total !== undefined" class="text-lg text-muted-foreground">/{{ total }}</span>
    </div>

    <slot name="badge">
      <div
        v-if="badge"
        class="mb-2 rounded-full px-2 py-1 text-xs font-medium"
        :class="badgeClass"
        role="status"
      >
        {{ badge }}
      </div>
    </slot>

    <span v-if="label" class="mb-1 text-sm text-muted-foreground">{{ label }}</span>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';
import { urgencyPalette, type UrgencyLevel } from '@/Composables/useDashboardCardStyles';

const props = withDefaults(defineProps<{
  /** The headline number. A string is allowed so callers can pre-format (e.g. "1.2k"). */
  value: number | string;
  /** Renders as a "/N" denominator after the value. */
  total?: number | string;
  label?: string;
  /** Drives the value colour and the default badge tint. */
  urgency?: UrgencyLevel;
  /** Short status pill shown beside the value. Override entirely with the `badge` slot. */
  badge?: string;
  ariaLabel?: string;
  class?: HTMLAttributes['class'];
}>(), {
  total: undefined,
  label: undefined,
  urgency: 'neutral',
  badge: undefined,
  ariaLabel: undefined,
  class: undefined,
});

const valueClass = computed(() => urgencyPalette.text[props.urgency]);
const badgeClass = computed(() => urgencyPalette.badge[props.urgency]);
</script>
