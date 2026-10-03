<template>
  <Popover :open="isOpen && !isDismissed" @update:open="handleOpenChange">
    <PopoverAnchor as-child>
      <div
        :class="cn('relative inline-block', props.class)"
        data-slot="spotlight-popover"
        v-bind="$attrs"
        @pointerenter="handlePointerEnter"
        @pointerleave="handlePointerLeave"
      >
        <slot />

        <!-- Inset, not an outline offset, so an overflow-hidden ancestor cannot clip the frame. -->
        <template v-if="showBadge && !isDismissed">
          <span
            class="pointer-events-none absolute inset-0 border border-brand bg-brand/4"
            aria-hidden="true"
            data-slot="spotlight-frame"
          />
          <button
            type="button"
            class="absolute top-0 right-0 z-10 flex size-6 items-start justify-end pointer-coarse:size-11"
            :aria-label="$t('tutorials.spotlight_what_is_new')"
            data-slot="spotlight-notch"
            @click.stop.prevent="openNow"
          >
            <span
              :class="[
                'size-2.5 origin-top-right bg-brand-fill [clip-path:polygon(0_0,100%_0,100%_100%)] transition-transform duration-200 ease-out',
                isOpen ? 'scale-150' : 'animate-spotlight-breathe motion-reduce:animate-none',
              ]"
            />
          </button>
        </template>
      </div>
    </PopoverAnchor>

    <PopoverContent
      :side
      :align
      :side-offset="8"
      :collision-padding="12"
      class="w-80 max-w-[calc(100vw-1.5rem)] rounded-none border border-t-2 border-border border-t-brand p-0 shadow-none"
      @open-auto-focus="handleOpenAutoFocus"
      @close-auto-focus.prevent
      @pointerenter="handlePointerEnter"
      @pointerleave="handlePointerLeave"
      @click.stop
    >
      <div class="space-y-1.5 p-4">
        <EyebrowLabel :text="$t('tutorials.spotlight_eyebrow')" />
        <p class="text-base font-semibold leading-tight">
          {{ title }}
        </p>
        <p class="text-sm leading-relaxed text-muted-foreground">
          {{ description }}
        </p>
      </div>
      <div class="flex justify-end border-t border-border px-4 py-2.5">
        <Button size="sm" variant="outline" voice="sentence" type="button" @click.stop="handleDismiss">
          {{ computedDismissText }}
        </Button>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref, type HTMLAttributes } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { EyebrowLabel } from '@/Components/Brand';
import { Button } from '@/Components/ui/button';
import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';
import { cn } from '@/Utils/Shadcn/utils';

defineOptions({ inheritAttrs: false });

interface Props {
  title: string;
  description: string;
  /** Preferred side; collision handling flips and shifts it to stay in the viewport. */
  side?: 'top' | 'right' | 'bottom' | 'left';
  align?: 'start' | 'center' | 'end';
  /** Show the brand frame and corner notch around the trigger. */
  showBadge?: boolean;
  isDismissed?: boolean;
  dismissText?: string;
  showDelay?: number;
  hideDelay?: number;
  class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
  side: 'bottom',
  align: 'start',
  showBadge: true,
  isDismissed: false,
  dismissText: undefined,
  showDelay: 0,
  hideDelay: 400,
  class: undefined,
});

const emit = defineEmits<{
  dismiss: [];
}>();

const isOpen = ref(false);
/** Only an explicit notch press moves focus into the panel; hover must not steal it from the page. */
const openedByNotch = ref(false);
let showTimeout: ReturnType<typeof setTimeout> | null = null;
let hideTimeout: ReturnType<typeof setTimeout> | null = null;

const computedDismissText = computed(() => props.dismissText ?? $t('tutorials.spotlight_got_it'));

function clearTimers() {
  if (showTimeout) {
    clearTimeout(showTimeout);
    showTimeout = null;
  }

  if (hideTimeout) {
    clearTimeout(hideTimeout);
    hideTimeout = null;
  }
}

function handlePointerEnter(event: PointerEvent) {
  // Touch fires pointerenter on tap too; there the notch is the way in, so the trigger keeps its action.
  if (props.isDismissed || event.pointerType !== 'mouse') return;

  clearTimers();

  if (isOpen.value) return;

  if (props.showDelay <= 0) {
    openedByNotch.value = false;
    isOpen.value = true;

    return;
  }

  showTimeout = setTimeout(() => {
    openedByNotch.value = false;
    isOpen.value = true;
  }, props.showDelay);
}

function handlePointerLeave(event: PointerEvent) {
  if (event.pointerType !== 'mouse' || openedByNotch.value) return;

  clearTimers();
  hideTimeout = setTimeout(() => {
    isOpen.value = false;
  }, props.hideDelay);
}

function openNow() {
  clearTimers();
  openedByNotch.value = true;
  isOpen.value = true;
}

function handleOpenChange(open: boolean) {
  clearTimers();
  isOpen.value = open;
}

function handleOpenAutoFocus(event: Event) {
  if (!openedByNotch.value) {
    event.preventDefault();
  }
}

function handleDismiss() {
  clearTimers();
  isOpen.value = false;
  emit('dismiss');
}

onUnmounted(clearTimers);
</script>
