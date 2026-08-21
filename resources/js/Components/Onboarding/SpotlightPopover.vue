<template>
  <div
    ref="triggerRef"
    class="relative inline-block cursor-pointer"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <slot />

    <!--
      Larger hover area around badge for easier interaction. Non-float only: its `-top-3 -right-3`
      offset relies on the trigger's own box, and an ancestor with `overflow: hidden` (e.g.
      ShowPageHero's rounded card) would otherwise clip it.
    -->
    <div
      v-if="showBadge && !isDismissed && !float"
      class="absolute -top-3 -right-3 z-10 h-8 w-8 flex items-center justify-center"
    >
      <span class="relative flex h-3 w-3">
        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary/75 opacity-75" />
        <span class="relative inline-flex h-3 w-3 rounded-full bg-primary" />
      </span>
    </div>

    <!-- float: teleport the badge alongside the panel so the same overflow-hidden ancestor can't clip it -->
    <Teleport v-if="float" to="body">
      <div
        v-if="showBadge && !isDismissed"
        class="fixed z-50 h-8 w-8 flex items-center justify-center"
        :style="badgeStyle"
      >
        <span class="relative flex h-3 w-3">
          <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary/75 opacity-75" />
          <span class="relative inline-flex h-3 w-3 rounded-full bg-primary" />
        </span>
      </div>
    </Teleport>

    <!--
      When `float` is set the panel is teleported to the body and positioned from the trigger's
      bounding rect. An ancestor with `overflow: auto` (the sidebar's scroll container) clips an
      absolutely positioned panel, which would otherwise render offscreen and look like nothing
      happened on hover.
    -->
    <Teleport v-if="float" to="body">
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div
          v-if="isOpen"
          class="fixed z-50 w-80 max-w-[calc(100vw-2rem)]"
          :style="floatingStyle"
          @mouseenter="handleMouseEnter"
          @mouseleave="handleMouseLeave"
          @click.stop
        >
          <div class="space-y-3 rounded-lg border bg-popover p-4 shadow-lg">
            <div class="flex items-start gap-3">
              <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10">
                <Sparkles class="h-4 w-4 text-primary" />
              </div>
              <div class="space-y-1.5">
                <h4 class="text-base font-semibold leading-tight">
                  {{ title }}
                </h4>
                <p class="text-sm leading-relaxed text-muted-foreground">
                  {{ description }}
                </p>
              </div>
            </div>

            <div class="flex justify-end">
              <Button size="sm" type="button" @click.stop="handleDismiss">
                {{ computedDismissText }}
              </Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <Transition
      v-else
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        :class="[
          'absolute z-50 w-80 max-w-[calc(100vw-2rem)]',
          positionClasses,
        ]"
        @click.stop
      >
        <div class="rounded-lg border bg-popover p-4 shadow-lg space-y-3">
          <!-- Header with title and sparkle icon -->
          <div class="flex items-start gap-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10">
              <Sparkles class="h-4 w-4 text-primary" />
            </div>
            <div class="space-y-1.5">
              <h4 class="font-semibold text-base leading-tight">
                {{ title }}
              </h4>
              <p class="text-sm text-muted-foreground leading-relaxed">
                {{ description }}
              </p>
            </div>
          </div>

          <!-- Action button -->
          <div class="flex justify-end">
            <Button size="sm" type="button" @click.stop="handleDismiss">
              {{ computedDismissText }}
            </Button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Sparkles } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';

interface Props {
  /**
   * Title of the spotlight
   */
  title: string;

  /**
   * Description of the spotlight
   */
  description: string;

  /**
   * Position of the tooltip
   */
  position?: 'top' | 'bottom' | 'left' | 'right' | 'top-right' | 'bottom-right';

  /**
   * Whether to show the pulsing badge
   */
  showBadge?: boolean;

  /**
   * Whether the spotlight has been dismissed
   */
  isDismissed?: boolean;

  /**
   * Custom dismiss button text
   */
  dismissText?: string;

  /**
   * Delay before showing tooltip (ms)
   */
  showDelay?: number;

  /**
   * Delay before hiding tooltip (ms)
   */
  hideDelay?: number;

  /**
   * Teleport the panel to the body and position it from the trigger's rect. Required when an
   * ancestor scroll container (`overflow: auto`) would otherwise clip it — e.g. the sidebar nav.
   */
  float?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  position: 'right',
  showBadge: true,
  isDismissed: false,
  dismissText: undefined,
  showDelay: 150,
  hideDelay: 400,
  float: false,
});

const emit = defineEmits<{
  dismiss: [];
}>();

const isOpen = ref(false);
const triggerRef = ref<HTMLElement | null>(null);
const floatingStyle = ref<Record<string, string>>({});
const badgeStyle = ref<Record<string, string>>({});
let showTimeout: ReturnType<typeof setTimeout> | null = null;
let hideTimeout: ReturnType<typeof setTimeout> | null = null;

const PANEL_WIDTH = 320;
const GAP = 12;
/** Half the badge's own box (`h-8 w-8`), so it centers on the trigger's corner. */
const BADGE_HALF = 16;

/** Anchor the teleported panel to the trigger, keeping it inside the viewport. */
function positionFloatingPanel() {
  const trigger = triggerRef.value;

  if (!trigger) {
    return;
  }

  const rect = trigger.getBoundingClientRect();
  const overflowsRight = rect.right + GAP + PANEL_WIDTH > window.innerWidth;

  const left = overflowsRight
    ? Math.max(GAP, rect.left - GAP - PANEL_WIDTH)
    : rect.right + GAP;

  floatingStyle.value = {
    left: `${left}px`,
    top: `${Math.max(GAP, Math.min(rect.top, window.innerHeight - 200))}px`,
  };
}

/** Anchor the teleported badge to the trigger's top-right corner (mirrors `-top-3 -right-3`). */
function positionBadge() {
  const trigger = triggerRef.value;

  if (!trigger) {
    return;
  }

  const rect = trigger.getBoundingClientRect();

  badgeStyle.value = {
    left: `${rect.right - BADGE_HALF}px`,
    top: `${rect.top - BADGE_HALF}px`,
  };
}

const computedDismissText = computed(() => props.dismissText ?? $t('tutorials.done'));

const positionClasses = computed(() => {
  switch (props.position) {
    case 'top':
      return 'bottom-full left-1/2 -translate-x-1/2 mb-3 origin-bottom';
    case 'top-right':
      // Anchored to the left edge, opening up and to the right — avoids
      // clipping against the viewport's left edge (e.g. the sidebar footer).
      return 'bottom-full left-0 mb-3 origin-bottom-left';
    case 'bottom':
      return 'top-full left-1/2 -translate-x-1/2 mt-3 origin-top';
    case 'bottom-right':
      // Anchored to the right edge, opening down and to the left — for a trigger pushed to the
      // far right of its row (e.g. `class="ml-auto"`), where a centered panel would overflow the
      // viewport's right edge.
      return 'top-full right-0 mt-3 origin-top-right';
    case 'left':
      return 'right-full top-0 mr-3 origin-right';
    case 'right':
      return 'left-full top-0 ml-3 origin-left';
    default:
      return 'left-full top-0 ml-3 origin-left';
  }
});

// The badge (unlike the panel) is visible whenever not dismissed, not only on hover, so it
// needs a position as soon as it can render — and to follow the trigger if the layout shifts.
if (props.float) {
  onMounted(() => {
    positionBadge();
    window.addEventListener('resize', positionBadge);
  });

  onUnmounted(() => {
    window.removeEventListener('resize', positionBadge);
  });

  watch([() => props.showBadge, () => props.isDismissed], () => positionBadge());
}

function handleMouseEnter() {
  if (props.isDismissed) return;

  if (hideTimeout) {
    clearTimeout(hideTimeout);
    hideTimeout = null;
  }

  showTimeout = setTimeout(() => {
    if (props.float) {
      positionFloatingPanel();
    }
    isOpen.value = true;
  }, props.showDelay);
}

function handleMouseLeave() {
  if (showTimeout) {
    clearTimeout(showTimeout);
    showTimeout = null;
  }

  hideTimeout = setTimeout(() => {
    isOpen.value = false;
  }, props.hideDelay);
}

function handleDismiss() {
  isOpen.value = false;
  emit('dismiss');
}
</script>
