<template>
  <div
    class="flex min-h-9 flex-wrap items-center justify-between gap-x-4 gap-y-2 border-t border-border pt-2.5"
    data-slot="collection-active-chips"
  >
    <div class="flex min-w-0 flex-wrap items-center gap-2">
      <template v-if="chips.length > 0">
        <span class="text-xs text-muted-foreground">{{ $t('Aktyvūs filtrai') }}:</span>
        <span
          v-for="chip in chips"
          :key="chip.id"
          class="inline-flex h-7 items-center gap-1 border border-border bg-secondary pl-2 text-xs"
        >
          {{ chip.label }}
          <button
            type="button"
            class="flex size-7 items-center justify-center text-muted-foreground hover:text-foreground pointer-coarse:size-9"
            :aria-label="$t('Šalinti filtrą :name', { name: chip.label })"
            @click="emit('remove', chip.id)"
          >
            <X class="size-3.5" aria-hidden="true" />
          </button>
        </span>
        <button
          type="button"
          class="h-7 px-1 text-xs text-muted-foreground underline underline-offset-4 hover:text-foreground"
          @click="emit('clear')"
        >
          {{ $t('Išvalyti visus') }}
        </button>
      </template>
    </div>

    <p class="text-xs tabular-nums text-muted-foreground" aria-live="polite">
      <template v-if="total !== null">
        {{ $t('Rasta :count', { count: String(total) }) }}
      </template>
    </p>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { X } from 'lucide-vue-next';

import type { CollectionChip } from '@/Composables/useCollectionSource';

defineProps<{
  chips: CollectionChip[];
  /** null before the first result, so the row keeps its height without a stale number. */
  total: number | null;
}>();

const emit = defineEmits<{
  remove: [id: string];
  clear: [];
}>();
</script>
