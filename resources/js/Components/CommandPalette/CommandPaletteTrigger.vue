<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <Button
          variant="outline"
          size="icon"
          class="rounded-full"
          @click="toggle"
        >
          <Search class="h-4 w-4" />
          <span class="sr-only">{{ $t('Ieškoti') }}</span>
        </Button>
      </TooltipTrigger>
      <TooltipContent side="bottom">
        <div class="flex items-center gap-2">
          <span>{{ $t('Ieškoti') }}</span>
          <kbd class="inline-flex h-5 items-center rounded bg-white/20 dark:bg-black/20 px-1.5 font-mono text-[10px] font-medium">
            {{ isMac ? '⌘K' : 'Ctrl+K' }}
          </kbd>
        </div>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Search } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { useCommandPalette } from '@/Composables/useCommandPalette';

const { toggle } = useCommandPalette();

// Detect Mac for keyboard shortcut display
const isMac = computed(() => {
  if (typeof navigator === 'undefined') return false;
  return navigator.platform.toUpperCase().indexOf('MAC') >= 0;
});
</script>
