<template>
  <div
    data-agenda-notes-menu
    class="z-50 max-h-64 w-56 overflow-y-auto border border-border bg-popover p-1"
  >
    <template v-if="items.length">
      <button
        v-for="(item, index) in items"
        :key="item.title"
        type="button"
        :class="[
          'flex w-full items-center gap-2 px-2 py-1.5 text-left text-sm transition-colors',
          index === selectedIndex ? 'bg-accent' : 'hover:bg-accent/60',
        ]"
        @click="selectItem(index)"
      >
        <component :is="item.icon" class="size-4 shrink-0 text-muted-foreground" />
        <span class="truncate text-foreground">{{ item.title }}</span>
      </button>
    </template>
    <p v-else class="px-2 py-1.5 text-xs text-muted-foreground">
      {{ $t('Nieko nerasta') }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { SlashCommandItem } from '@/Components/AgendaItems/notesSlashCommands';

const props = defineProps<{
  items: SlashCommandItem[];
  command: (item: SlashCommandItem) => void;
}>();

const selectedIndex = ref(0);

watch(() => props.items, () => {
  selectedIndex.value = 0;
});

function selectItem(index: number) {
  const item = props.items[index];
  if (item) {
    props.command(item);
  }
}

function onKeyDown({ event }: { event: KeyboardEvent }): boolean {
  if (!props.items.length) {
    return false;
  }
  if (event.key === 'ArrowUp') {
    selectedIndex.value = (selectedIndex.value + props.items.length - 1) % props.items.length;
    return true;
  }
  if (event.key === 'ArrowDown') {
    selectedIndex.value = (selectedIndex.value + 1) % props.items.length;
    return true;
  }
  if (event.key === 'Enter') {
    selectItem(selectedIndex.value);
    return true;
  }
  return false;
}

defineExpose({ onKeyDown });
</script>
