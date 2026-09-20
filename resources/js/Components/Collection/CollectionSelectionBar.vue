<template>
  <div
    class="fixed inset-x-4 bottom-4 z-40 flex items-center justify-between gap-3 rounded-lg border bg-background p-3 shadow-lg sm:left-auto sm:right-6"
    data-slot="collection-selection-bar"
  >
    <span class="text-sm font-medium">{{ countLabel }}: {{ count }}</span>

    <div class="flex items-center gap-2">
      <slot />
      <Button variant="ghost" size="icon" :aria-label="$t('Close')" @click="emit('clear')">
        <X class="size-4" />
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { X } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';

const props = defineProps<{
  count: number;
  countLabel: string;
}>();

const emit = defineEmits<{
  clear: [];
}>();

</script>
