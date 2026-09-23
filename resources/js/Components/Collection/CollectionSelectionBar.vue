<template>
  <div
    class="fixed inset-x-4 bottom-4 z-40 flex flex-wrap items-center justify-between gap-3 border border-foreground/80 bg-popover p-3 text-popover-foreground sm:left-auto sm:right-6"
    role="region"
    :aria-label="countLabel"
    data-slot="collection-selection-bar"
  >
    <span class="text-xs font-bold uppercase tracking-wide">
      {{ countLabel }}: <span class="tabular-nums text-brand">{{ count }}</span>
    </span>

    <div class="flex items-center gap-2">
      <slot />
      <Button variant="ghost" size="icon" :aria-label="$t('Atšaukti žymėjimą')" @click="emit('clear')">
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
