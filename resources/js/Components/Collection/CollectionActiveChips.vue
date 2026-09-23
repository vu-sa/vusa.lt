<template>
  <div class="flex min-h-9 flex-wrap items-center gap-2" data-slot="collection-active-chips">
    <div class="flex min-w-0 flex-wrap items-center gap-2">
      <template v-if="chips.length > 0">
        <span
          v-for="chip in chips"
          :key="chip.id"
          class="inline-flex h-8 items-center gap-1 border border-border bg-background pl-2.5 text-xs font-medium text-foreground"
        >
          {{ chip.label }}
          <button
            type="button"
            class="flex size-8 items-center justify-center text-muted-foreground hover:text-brand pointer-coarse:size-11"
            :aria-label="$t('Šalinti filtrą :name', { name: chip.label })"
            @click="emit('remove', chip.id)"
          >
            <X class="size-3.5" aria-hidden="true" />
          </button>
        </span>
        <button
          type="button"
          class="inline-flex h-8 items-center gap-1.5 px-1 text-xs font-bold uppercase tracking-wide text-brand hover:text-foreground pointer-coarse:min-h-11"
          @click="emit('clear')"
        >
          <X class="size-3.5" aria-hidden="true" />
          {{ $t('Išvalyti visus') }}
        </button>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { X } from 'lucide-vue-next';

import type { CollectionChip } from '@/Composables/useCollectionSource';

defineProps<{
  chips: CollectionChip[];
}>();

const emit = defineEmits<{
  remove: [id: string];
  clear: [];
}>();
</script>
