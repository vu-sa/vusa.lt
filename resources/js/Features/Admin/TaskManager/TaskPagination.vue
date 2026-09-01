<template>
  <div
    v-if="lastPage > 1"
    class="flex items-center justify-between border-t border-zinc-200 px-4 py-3 dark:border-zinc-800 sm:px-6"
  >
    <div class="text-sm text-zinc-500 dark:text-zinc-400">
      {{ from }} - {{ to }} / {{ total }}
    </div>
    <div class="flex items-center gap-2">
      <Button
        variant="outline"
        size="sm"
        :disabled="currentPage === 1"
        :aria-label="$t('tables.previous_page')"
        @click="emit('change', currentPage - 1)"
      >
        <ChevronLeftIcon class="h-4 w-4" />
      </Button>
      <span class="text-sm tabular-nums text-zinc-600 dark:text-zinc-400">
        {{ currentPage }} / {{ lastPage }}
      </span>
      <Button
        variant="outline"
        size="sm"
        :disabled="currentPage === lastPage"
        :aria-label="$t('tables.next_page')"
        @click="emit('change', currentPage + 1)"
      >
        <ChevronRightIcon class="h-4 w-4" />
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronLeft as ChevronLeftIcon, ChevronRight as ChevronRightIcon } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';

defineProps<{
  currentPage: number;
  lastPage: number;
  total: number;
  from: number | null;
  to: number | null;
}>();

const emit = defineEmits<{
  (e: 'change', page: number): void;
}>();
</script>
