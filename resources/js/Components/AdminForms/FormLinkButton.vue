<template>
  <div class="group/link flex items-center gap-1">
    <!-- Label badge (if provided) -->
    <span v-if="label"
      class="hidden text-[10px] font-semibold uppercase tracking-wider text-muted-foreground/70 sm:inline">
      {{ label }}
    </span>

    <!-- Link with URL -->
    <Tooltip>
      <TooltipTrigger as-child>
        <a :href="url" target="_blank" rel="noopener noreferrer"
          class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-sm transition-all duration-150" :class="[
            'text-muted-foreground hover:text-foreground',
            'hover:bg-zinc-100 dark:hover:bg-zinc-800',
            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1',
          ]">
          <!-- Custom icon or default globe -->
          <component :is="icon" v-if="icon" class="h-3.5 w-3.5 shrink-0" />
          <IFluentGlobe24Regular v-else class="h-3.5 w-3.5 shrink-0" />

          <!-- URL text - hidden on compact mobile -->
          <span class="max-w-[120px] truncate font-mono text-xs lg:max-w-[200px]"
            :class="compact ? 'hidden sm:inline' : ''">
            {{ truncatedUrl }}
          </span>

          <!-- External link indicator -->
          <IFluentArrowUpRight24Regular
            class="h-3 w-3 shrink-0 opacity-0 transition-opacity duration-150 group-hover/link:opacity-100" />
        </a>
      </TooltipTrigger>
      <TooltipContent side="bottom" class="max-w-sm">
        <p class="font-mono text-xs break-all">
          {{ displayUrl }}
        </p>
      </TooltipContent>
    </Tooltip>

    <!-- Copy button -->
    <Tooltip v-if="showCopy">
      <TooltipTrigger as-child>
        <Button variant="ghost" size="icon"
          class="h-6 w-6 shrink-0 opacity-0 transition-opacity duration-150 group-hover/link:opacity-100 focus-visible:opacity-100"
          :class="copied ? 'opacity-100' : ''" @click.stop="copyUrl">
          <IFluentCheckmark24Regular v-if="copied" class="h-3.5 w-3.5 text-green-600 dark:text-green-400" />
          <IFluentCopy24Regular v-else class="h-3.5 w-3.5" />
        </Button>
      </TooltipTrigger>
      <TooltipContent side="bottom">
        {{ copied ? $t('Nukopijuota!') : $t('Kopijuoti nuorodą') }}
      </TooltipContent>
    </Tooltip>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, type Component } from 'vue';
import { useClipboard } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';

const props = withDefaults(defineProps<{
  /** The URL to link to */
  url: string;
  /** Optional label (e.g., "Public", "Admin") */
  label?: string;
  /** Optional icon component */
  icon?: Component;
  /** Show copy button */
  showCopy?: boolean;
  /** Compact mode - icon only on mobile */
  compact?: boolean;
}>(), {
  showCopy: true,
  compact: false,
});

const copied = ref(false);
const { copy } = useClipboard();

const copyUrl = async () => {
  await copy(props.url);
  copied.value = true;
  setTimeout(() => {
    copied.value = false;
  }, 2000);
};

// Strip protocol for display
const displayUrl = computed(() => {
  return props.url.replace(/^https?:\/\//, '');
});

// Truncate for display
const truncatedUrl = computed(() => {
  const stripped = displayUrl.value;
  if (stripped.length > 40) {
    return `${stripped.slice(0, 37)}...`;
  }
  return stripped;
});
</script>
