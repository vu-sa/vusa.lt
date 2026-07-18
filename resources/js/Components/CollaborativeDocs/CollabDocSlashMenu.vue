<template>
  <div
    data-collab-doc-menu
    class="z-50 max-h-64 w-56 overflow-y-auto rounded-lg border border-zinc-200 bg-popover p-1 shadow-md dark:border-zinc-700"
  >
    <template v-if="items.length">
      <button
        v-for="(item, index) in items"
        :key="item.title"
        type="button"
        :class="[
          'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm transition-colors',
          index === selectedIndex ? 'bg-zinc-100 dark:bg-zinc-800' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/50',
        ]"
        @click="selectItem(index)"
      >
        <component :is="item.icon" class="h-4 w-4 shrink-0 text-zinc-500 dark:text-zinc-400" />
        <span class="truncate text-zinc-800 dark:text-zinc-100">{{ item.title }}</span>
      </button>
    </template>
    <p v-else class="px-2 py-1.5 text-xs text-zinc-400">
      {{ $t('Nieko nerasta') }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { SlashCommandItem } from '@/Components/CollaborativeDocs/slashCommands';

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
