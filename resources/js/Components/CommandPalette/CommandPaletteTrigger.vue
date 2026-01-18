<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <Button
          variant="outline"
          size="sm"
          class="relative h-9 w-9 p-0 xl:h-9 xl:w-64 xl:justify-start xl:px-3 xl:py-2 text-muted-foreground hover:text-foreground transition-colors"
          @click="toggle"
        >
          <Search class="size-4 xl:mr-2" />
          <span class="hidden xl:inline-flex text-sm">{{ $t('Ieškoti...') }}</span>
          <div class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 hidden xl:flex items-center gap-0.5">
            <kbd class="inline-flex h-5 items-center rounded border bg-muted/60 px-1.5 font-mono text-[10px] font-medium text-muted-foreground/70">
              {{ isMac ? '⌘' : 'Ctrl' }}
            </kbd>
            <kbd class="inline-flex h-5 items-center rounded border bg-muted/60 px-1.5 font-mono text-[10px] font-medium text-muted-foreground/70">
              K
            </kbd>
          </div>
        </Button>
      </TooltipTrigger>
      <TooltipContent side="bottom" class="xl:hidden">
        <div class="flex items-center gap-2">
          <span>{{ $t('Ieškoti') }}</span>
          <kbd class="inline-flex h-5 items-center rounded bg-muted px-1.5 font-mono text-[10px]">
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
