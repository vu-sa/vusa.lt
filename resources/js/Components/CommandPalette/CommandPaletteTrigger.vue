<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <Button
          variant="outline"
          size="icon"
          class="relative h-9 w-9 rounded-full lg:h-9 lg:w-56 xl:w-64 lg:rounded-md lg:justify-start lg:px-3 lg:py-2"
          @click="toggle"
        >
          <Search class="size-4 text-primary lg:text-muted-foreground lg:mr-2" />
          <span class="hidden lg:inline-flex text-sm text-muted-foreground">{{ $t('Ieškoti...') }}</span>
          <kbd class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 hidden lg:inline-flex h-5 items-center rounded border bg-muted/60 px-1.5 font-mono text-[10px] font-medium text-muted-foreground/70">
            {{ isMac ? '⌘K' : 'Ctrl+K' }}
          </kbd>
        </Button>
      </TooltipTrigger>
      <TooltipContent side="bottom" class="lg:hidden">
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
import { computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { Search } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip'
import { useCommandPalette } from '@/Composables/useCommandPalette'

const { toggle } = useCommandPalette()

// Detect Mac for keyboard shortcut display
const isMac = computed(() => {
  if (typeof navigator === 'undefined') return false
  return navigator.platform.toUpperCase().indexOf('MAC') >= 0
})
</script>
