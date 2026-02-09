<template>
  <Dialog :open="isOpen" @update:open="updateDialogState">
    <DialogContent
      :show-close-button="false"
      class="sm:max-w-6xl w-[95vw] h-[75vh] max-h-[calc(100vh-4rem)] p-0 overflow-hidden grid grid-rows-[auto_1fr] data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
    >
      <DialogHeader class="px-6 pt-3 pb-0">
        <div class="flex items-center justify-between">
          <DialogTitle>{{ $t('search.search') }}</DialogTitle>
          <div class="flex items-center gap-2">
            <!-- Filter Toggle Button -->
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              :class="{ 'bg-muted': showFilters }"
              @click="$emit('toggleFilters')"
            >
              <IconFilter class="w-4 h-4" />
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
              :class="{ 'bg-muted': showKeyboardHelp }"
              :title="$t('search.keyboard_shortcuts')"
              @click="$emit('toggleKeyboardHelp')"
            >
              <IconQuestionCircle class="w-4 h-4" />
              <span class="sr-only">{{ $t('search.show_keyboard_shortcuts') }}</span>
            </Button>

            <!-- Custom Close Button -->
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              :title="$t('search.cancel')"
              @click="updateDialogState(false)"
            >
              <IconClose class="w-4 h-4" />
              <span class="sr-only">Close</span>
            </Button>
          </div>
        </div>
        <DialogDescription class="sr-only">
          {{ $t('search.search_across_types') }}
        </DialogDescription>
      </DialogHeader>

      <!-- Show message if Typesense is not configured -->
      <div v-if="!searchClient" class="p-6 text-center">
        <p class="text-muted-foreground">
          {{ $t('search.search_unavailable') }}
        </p>
      </div>

      <slot v-else />
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import IconFilter from '~icons/fluent/filter16-regular';
import IconQuestionCircle from '~icons/fluent/question-circle20-regular';
import IconClose from '~icons/fluent/dismiss20-regular';

interface SearchDialogProps {
  isOpen: boolean;
  searchClient: any;
  totalResultCount: number;
  showFilters: boolean;
  showKeyboardHelp: boolean;
}

defineProps<SearchDialogProps>();

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void;
  (e: 'close'): void;
  (e: 'open'): void;
  (e: 'toggleFilters'): void;
  (e: 'toggleKeyboardHelp'): void;
}>();

const updateDialogState = (value: boolean) => {
  emit('update:isOpen', value);
  if (!value) {
    emit('close');
  }
  else {
    emit('open');
  }
};
</script>
