<template>
  <Dialog :open="isOpen" @update:open="updateDialogState">
    <DialogContent class="sm:max-w-6xl w-[95vw] h-[75vh] max-h-[calc(100vh-4rem)] p-0 overflow-hidden grid grid-rows-[auto_1fr] data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0">
      <DialogHeader class="px-6 pt-4 pb-0">
        <div class="flex items-center justify-between">
          <DialogTitle>{{ $t('search.search') }}</DialogTitle>
          <div class="flex items-center gap-2">
            <!-- Filter Toggle Button -->
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              @click="$emit('toggleFilters')"
              :class="{ 'bg-muted': showFilters }"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
              </svg>
              <span class="sr-only">{{ $t('search.toggle_filters') }}</span>
            </Button>
            <!-- Total Results Count -->
            <Badge v-if="totalResultCount > 0" variant="secondary" class="text-xs">
              {{ totalResultCount }}
            </Badge>
            
            <!-- Help/Shortcuts Button -->
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              @click="$emit('toggleKeyboardHelp')"
              :class="{ 'bg-muted': showKeyboardHelp }"
              :title="$t('search.keyboard_shortcuts')"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                <path d="M12 17h.01"/>
              </svg>
              <span class="sr-only">{{ $t('search.show_keyboard_shortcuts') }}</span>
            </Button>
          </div>
        </div>
        <DialogDescription class="sr-only">
          {{ $t('search.search_across_types') }}
        </DialogDescription>
      </DialogHeader>

      <!-- Show message if Typesense is not configured -->
      <div v-if="!searchClient" class="p-6 text-center">
        <p class="text-muted-foreground">{{ $t('search.search_unavailable') }}</p>
      </div>

      <slot v-else />
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'

interface SearchDialogProps {
  isOpen: boolean
  searchClient: any
  totalResultCount: number
  showFilters: boolean
  showKeyboardHelp: boolean
}

defineProps<SearchDialogProps>()

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'close'): void
  (e: 'open'): void
  (e: 'toggleFilters'): void
  (e: 'toggleKeyboardHelp'): void
}>()

const updateDialogState = (value: boolean) => {
  emit('update:isOpen', value)
  if (!value) {
    emit('close')
  } else {
    emit('open')
  }
}
</script>