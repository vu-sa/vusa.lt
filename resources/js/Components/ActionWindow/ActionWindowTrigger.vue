<template>
  <SpotlightPopover
    v-if="hasAnyAction"
    :title="$t('action_window.spotlight.title')"
    :description="$t('action_window.spotlight.description')"
    :is-dismissed="spotlight.isDismissed.value"
    :position="spotlightPosition"
    :float
    :class="fullWidth ? 'block w-full' : undefined"
    @dismiss="spotlight.dismiss"
  >
    <!--
      No surface of its own: in the sidebar it has to sit among the nav rows, and a
      filled slab read as an alert rather than an invitation. The colour lives on the
      icon tile alone, which is enough to mark it as the primary thing here.
    -->
    <button
      type="button"
      data-testid="action-window-trigger"
      :class="cn(
        'group flex items-center gap-2 rounded-md px-2 py-2 text-left text-sm',
        'transition-colors duration-150 hover:bg-sidebar-accent/60',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
        variant === 'standalone' && 'rounded-lg border border-border/70 px-3 hover:bg-accent',
        fullWidth && 'w-full',
        props.class,
      )"
      @click="openWindow"
    >
      <span
        :class="cn(
          'flex size-7 shrink-0 items-center justify-center rounded-md bg-gradient-to-br transition-opacity',
          'opacity-90 group-hover:opacity-100',
          TRIGGER_TINT,
        )"
      >
        <Sparkles class="size-4" :stroke-width="2" />
      </span>
      <span :class="cn('truncate font-medium', labelClass)">{{ $t('action_window.trigger') }}</span>
    </button>
  </SpotlightPopover>
</template>

<script setup lang="ts">
import { Sparkles } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useActionWindowCatalog } from '@/Composables/useActionWindowCatalog';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { cn } from '@/Utils/Shadcn/utils';

/** The same tint the sidebar quick actions used, so the button inherits their colour. */
const TRIGGER_TINT = 'from-amber-500/25 to-orange-500/20 dark:from-amber-400/20 dark:to-orange-400/15';

const props = withDefaults(defineProps<{
  /** `sidebar` sits among nav rows; `standalone` gets an outline to stand off a hero. */
  variant?: 'sidebar' | 'standalone';
  fullWidth?: boolean;
  /** Teleport the spotlight out of clipping ancestors (the sidebar scroll area). */
  float?: boolean;
  spotlightPosition?: 'top' | 'bottom' | 'left' | 'right' | 'top-right' | 'bottom-right';
  /** Lets the sidebar hide the label when collapsed to icons. */
  labelClass?: HTMLAttributes['class'];
  class?: HTMLAttributes['class'];
}>(), {
  variant: 'sidebar',
  spotlightPosition: 'bottom',
});

const { open } = useActionWindow();
const { hasAnyAction } = useActionWindowCatalog();

const spotlight = useFeatureSpotlight('action-window-v1', { position: props.spotlightPosition });

const openWindow = () => {
  // Engaging with the feature is the real dismissal signal; the popover's own
  // button is only the fallback for someone who reads it and moves on.
  spotlight.dismiss();
  open();
};
</script>
