<template>
  <!-- Desktop: Tooltip on hover -->
  <TooltipProvider v-if="!isTouchDevice">
    <Tooltip>
      <TooltipTrigger as-child>
        <button 
          type="button" 
          class="inline-flex items-center justify-center rounded-full p-0.5 text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary"
          :aria-label="$t('Daugiau informacijos')"
        >
          <Info class="h-3.5 w-3.5" />
        </button>
      </TooltipTrigger>
      <TooltipContent class="max-w-xs">
        <p class="text-xs leading-relaxed">{{ text }}</p>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
  
  <!-- Mobile: Popover on click -->
  <Popover v-else>
    <PopoverTrigger as-child>
      <button 
        type="button" 
        class="inline-flex items-center justify-center rounded-full p-0.5 text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary"
        :aria-label="$t('Daugiau informacijos')"
      >
        <Info class="h-3.5 w-3.5" />
      </button>
    </PopoverTrigger>
    <PopoverContent class="w-64 p-3">
      <p class="text-xs leading-relaxed text-zinc-700 dark:text-zinc-300">{{ text }}</p>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Info } from 'lucide-vue-next';
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/Components/ui/tooltip';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

defineProps<{
  text: string;
}>();

const isTouchDevice = ref(false);

onMounted(() => {
  // Detect touch device for mobile-friendly tooltips
  isTouchDevice.value = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
});
</script>
