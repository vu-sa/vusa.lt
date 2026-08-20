<template>
  <Card :class="[dashboardCardClasses, props.class]" role="region" :aria-label="ariaLabel ?? title">
    <!-- Corner accent: a diagonal wedge tinted by urgency. -->
    <div :class="accentClass ? [STATUS_INDICATOR_BASE, accentClass] : statusIndicatorClasses" aria-hidden="true" />

    <CardHeader class="relative z-10 pb-3">
      <div class="flex items-center justify-between gap-2">
        <CardTitle class="flex min-w-0 items-center gap-2 text-base font-semibold">
          <slot name="icon">
            <component :is="icon" v-if="icon" :class="iconClasses" aria-hidden="true" />
          </slot>
          <span class="truncate">{{ title }}</span>
        </CardTitle>

        <div v-if="$slots['header-action']" class="flex shrink-0 items-center gap-2">
          <slot name="header-action" />
        </div>
      </div>
    </CardHeader>

    <CardContent :class="['relative z-10 flex-1', contentClass]">
      <slot />
    </CardContent>

    <CardFooter v-if="$slots.footer" :class="[dashboardCardFooterClasses, 'relative z-10 p-4']">
      <slot name="footer" />
    </CardFooter>
  </Card>
</template>

<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue';
import { computed } from 'vue';

import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import {
  STATUS_INDICATOR_BASE,
  dashboardCardClasses,
  dashboardCardFooterClasses,
  useDashboardCardStyles,
  type UrgencyLevel,
} from '@/Composables/useDashboardCardStyles';

const props = withDefaults(defineProps<{
  title: string;
  icon?: Component;
  /** Tints the corner accent and the header icon. */
  urgency?: UrgencyLevel;
  /**
   * Escape hatch for cards whose accent doesn't fit the four urgency levels
   * (e.g. a fixed neutral wedge). Replaces the tint, keeps the wedge geometry.
   */
  accentClass?: HTMLAttributes['class'];
  /** Defaults to `title`; set when the visible title is not descriptive on its own. */
  ariaLabel?: string;
  /** Extra classes for the content area (e.g. centring, min-height). */
  contentClass?: HTMLAttributes['class'];
  class?: HTMLAttributes['class'];
}>(), {
  icon: undefined,
  urgency: 'neutral',
  accentClass: undefined,
  ariaLabel: undefined,
  contentClass: undefined,
  class: undefined,
});

const { statusIndicatorClasses, iconClasses } = useDashboardCardStyles(
  computed(() => props.urgency),
);
</script>
