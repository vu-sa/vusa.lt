<template>
  <div 
    class="relative inline-block cursor-pointer"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <slot />
    
    <!-- Larger hover area around badge for easier interaction -->
    <div 
      v-if="showBadge && !isDismissed" 
      class="absolute -top-3 -right-3 z-10 h-8 w-8 flex items-center justify-center"
    >
      <span class="relative flex h-3 w-3">
        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary/75 opacity-75" />
        <span class="relative inline-flex h-3 w-3 rounded-full bg-primary" />
      </span>
    </div>
    
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
        @click.stop
        :class="[
          'absolute z-50 w-80 max-w-[calc(100vw-2rem)]',
          positionClasses,
        ]"
      >
        <div class="rounded-lg border bg-popover p-4 shadow-lg space-y-3">
          <!-- Header with title and sparkle icon -->
          <div class="flex items-start gap-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10">
              <Sparkles class="h-4 w-4 text-primary" />
            </div>
            <div class="space-y-1.5">
              <h4 class="font-semibold text-base leading-tight">{{ title }}</h4>
              <p class="text-sm text-muted-foreground leading-relaxed">{{ description }}</p>
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
import { computed, ref } from 'vue';
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
  position?: 'top' | 'bottom' | 'left' | 'right';
  
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
}

const props = withDefaults(defineProps<Props>(), {
  position: 'right',
  showBadge: true,
  isDismissed: false,
  dismissText: undefined,
  showDelay: 150,
  hideDelay: 400,
});

const emit = defineEmits<{
  'dismiss': [];
}>();

const isOpen = ref(false);
let showTimeout: ReturnType<typeof setTimeout> | null = null;
let hideTimeout: ReturnType<typeof setTimeout> | null = null;

const computedDismissText = computed(() => props.dismissText ?? $t('tutorials.done'));

const positionClasses = computed(() => {
  switch (props.position) {
    case 'top':
      return 'bottom-full left-1/2 -translate-x-1/2 mb-3 origin-bottom';
    case 'bottom':
      return 'top-full left-1/2 -translate-x-1/2 mt-3 origin-top';
    case 'left':
      return 'right-full top-0 mr-3 origin-right';
    case 'right':
      return 'left-full top-0 ml-3 origin-left';
    default:
      return 'left-full top-0 ml-3 origin-left';
  }
});

function handleMouseEnter() {
  if (props.isDismissed) return;
  
  if (hideTimeout) {
    clearTimeout(hideTimeout);
    hideTimeout = null;
  }
  
  showTimeout = setTimeout(() => {
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
