<template>
  <div class="relative">
    <img :src :alt :loading :style="objectPosition ? `object-position: ${objectPosition}` : ''" :class="[
      'w-full object-cover rounded-xl shadow-lg transition-transform duration-300',
      heightClass,
      { 'group-hover:scale-105': hoverScale }
    ]">

    <!-- Decorative elements -->
    <DecorativeElement v-for="decoration in decorations" :key="`${decoration.type}-${decoration.position}`"
      :type="decoration.type" :position="decoration.position" :size="decoration.size" />

    <!-- Icon overlay -->
    <div v-if="icon"
      class="absolute top-4 right-4 w-10 h-10 bg-white/90 dark:bg-zinc-800/90 rounded-full flex items-center justify-center shadow-sm">
      <component :is="icon" class="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
    </div>

    <!-- Overlay content (like the overlapping text card in hero) -->
    <div v-if="hasOverlayContent || forceOverlayContent" :class="[
      'absolute rounded-xl shadow-xl border border-zinc-100 dark:border-zinc-700',
      overlayPositionClass,
      overlaySize,
      overlayStyle === 'backdrop'
        ? ['bg-white/95 dark:bg-zinc-800/95 backdrop-blur-sm', overlayPaddingClass]
        : ['bg-white dark:bg-zinc-800', overlayPaddingClass]
    ]">
      <slot name="overlay-content">
        <div class="flex items-center space-x-1 sm:space-x-2 md:space-x-3 mb-1 sm:mb-2">
          <div class="w-2 h-2 sm:w-2.5 sm:h-2.5 md:w-3 md:h-3 bg-vusa-yellow rounded-full" />
          <span :class="[
            'font-medium text-zinc-600 dark:text-zinc-400',
            overlayStyle === 'backdrop' ? 'text-xs' : 'text-xs sm:text-sm'
          ]">
            {{ overlayContent?.title }}
          </span>
        </div>
        <p :class="[
          'text-zinc-500 dark:text-zinc-500',
          overlayStyle === 'backdrop' ? 'text-xs' : 'text-xs sm:text-sm'
        ]">
          {{ overlayContent?.subtitle }}
        </p>
      </slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';

import DecorativeElement from '@/Components/ui/DecorativeElement.vue';

interface DecorationConfig {
  type: 'circle' | 'line' | 'square';
  position: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
  size: 'sm' | 'md' | 'lg';
}

interface OverlayContent {
  title: string;
  subtitle: string;
}

type OverlayCorner = 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';

interface Props {
  src: string;
  alt: string;
  loading?: 'lazy' | 'eager';
  height?: 'sm' | 'md' | 'lg' | 'xl' | 'custom';
  heightClass?: string; // For custom heights
  decorations?: DecorationConfig[];
  icon?: Component;
  overlayContent?: OverlayContent;
  /**
   * Free-text position classes — kept for back-compat with existing authored content
   * (e.g. MembershipPage.vue's `overlay-position="bottom-4 left-4"`). Ignored whenever
   * `overlayCorner` is set; that's the authorable alternative (see below).
   */
  overlayPosition?: string;
  overlaySize?: string;
  overlayStyle?: 'default' | 'backdrop';
  objectPosition?: string;
  hoverScale?: boolean;
  /**
   * Authorable corner + containment for the overlay card, superseding the raw
   * `overlayPosition` string once set. `overlayOverhang: true` reproduces the old
   * default (card straddles the image edge); `false` (the default) keeps the whole
   * card inside the image, which is what most editors actually want — a card that
   * "protrudes" past the rounded corner reads as a layout bug more often than a look.
   */
  overlayCorner?: OverlayCorner;
  overlayOverhang?: boolean;
  overlayPadding?: 'sm' | 'md' | 'lg';
  /** Lets an editor render empty overlay fields in their final visual position. */
  forceOverlayContent?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: 'lazy',
  height: 'md',
  decorations: () => [],
  hoverScale: false,
  overlayPosition: '-bottom-3 -left-3 sm:-bottom-4 sm:-left-4 md:-bottom-6 md:-left-6 2xl:-bottom-8 2xl:-left-8',
  overlaySize: 'max-w-[200px] sm:max-w-xs',
  overlayStyle: 'default',
  overlayOverhang: false,
  overlayPadding: 'md',
});

// `overlayContent` may be present as an object with empty strings (e.g. a hero
// block's default json_content before an author fills it in) — render the overlay
// card only when there's actually something to show in it.
const hasOverlayContent = computed(() => !!(props.overlayContent?.title || props.overlayContent?.subtitle));

const OVERHANG_POSITION_CLASS: Record<OverlayCorner, string> = {
  'bottom-left': '-bottom-3 -left-3 sm:-bottom-4 sm:-left-4 md:-bottom-6 md:-left-6 2xl:-bottom-8 2xl:-left-8',
  'bottom-right': '-bottom-3 -right-3 sm:-bottom-4 sm:-right-4 md:-bottom-6 md:-right-6 2xl:-bottom-8 2xl:-right-8',
  'top-left': '-top-3 -left-3 sm:-top-4 sm:-left-4 md:-top-6 md:-left-6 2xl:-top-8 2xl:-left-8',
  'top-right': '-top-3 -right-3 sm:-top-4 sm:-right-4 md:-top-6 md:-right-6 2xl:-top-8 2xl:-right-8',
};

// Positive insets — the card sits fully inside the image's own rounded corners,
// instead of straddling them.
const CONTAINED_POSITION_CLASS: Record<OverlayCorner, string> = {
  'bottom-left': 'bottom-3 left-3 sm:bottom-4 sm:left-4',
  'bottom-right': 'bottom-3 right-3 sm:bottom-4 sm:right-4',
  'top-left': 'top-3 left-3 sm:top-4 sm:left-4',
  'top-right': 'top-3 right-3 sm:top-4 sm:right-4',
};

const overlayPositionClass = computed(() => {
  if (!props.overlayCorner) return props.overlayPosition;

  return props.overlayOverhang
    ? OVERHANG_POSITION_CLASS[props.overlayCorner]
    : CONTAINED_POSITION_CLASS[props.overlayCorner];
});

const OVERLAY_PADDING_CLASS: Record<'sm' | 'md' | 'lg', string> = {
  sm: 'p-2',
  md: 'p-2.5 sm:p-3 md:p-4',
  lg: 'p-3 sm:p-4 md:p-6 2xl:p-6',
};

const overlayPaddingClass = computed(() => OVERLAY_PADDING_CLASS[props.overlayPadding]);

const heightClass = computed(() => {
  if (props.heightClass) return props.heightClass;

  const heights = {
    sm: 'h-32 md:h-40',
    md: 'h-40 md:h-52',
    lg: 'h-52 md:h-64',
    xl: 'h-64 md:h-80',
    custom: '',
  };
  return heights[props.height];
});
</script>
