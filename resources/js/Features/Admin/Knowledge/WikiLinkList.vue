<template>
  <div
    data-slot="wiki-link-menu"
    role="listbox"
    :aria-label="$t('learning.wiki.link_search')"
    class="z-50 max-h-72 w-80 max-w-[calc(100vw-2rem)] overflow-y-auto border border-border bg-popover p-1 shadow-md"
  >
    <template v-if="items.length">
      <button
        v-for="(item, index) in items"
        :key="item.id"
        type="button"
        role="option"
        :aria-selected="index === selectedIndex"
        :class="[
          'flex w-full flex-col items-start gap-0.5 px-2.5 py-2 text-left transition-colors',
          index === selectedIndex ? 'bg-accent' : 'hover:bg-accent/60',
        ]"
        @click="selectItem(index)"
      >
        <span class="flex w-full min-w-0 items-baseline gap-2">
          <span class="truncate text-sm font-semibold text-foreground">{{ item.title }}</span>
          <span v-if="item.abbreviation" class="shrink-0 text-xs font-bold tracking-wide text-muted-foreground">{{ item.abbreviation }}</span>
        </span>
        <span v-if="item.summary" class="line-clamp-1 text-xs text-muted-foreground">{{ item.summary }}</span>
      </button>
    </template>
    <p v-else class="px-2.5 py-2 text-xs text-muted-foreground">
      {{ $t('learning.wiki.link_none') }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

import type { WikiIndexEntry } from '@/Types/learning';

const props = defineProps<{
  items: WikiIndexEntry[];
  command: (item: WikiIndexEntry) => void;
}>();

const selectedIndex = ref(0);

watch(() => props.items, () => {
  selectedIndex.value = 0;
});

function selectItem(index: number) {
  const item = props.items[index];
  if (item) props.command(item);
}

function onKeyDown({ event }: { event: KeyboardEvent }): boolean {
  if (!props.items.length) return false;
  if (event.key === 'ArrowUp') {
    selectedIndex.value = (selectedIndex.value + props.items.length - 1) % props.items.length;
    return true;
  }
  if (event.key === 'ArrowDown') {
    selectedIndex.value = (selectedIndex.value + 1) % props.items.length;
    return true;
  }
  if (event.key === 'Enter' || event.key === 'Tab') {
    selectItem(selectedIndex.value);
    return true;
  }
  return false;
}

defineExpose({ onKeyDown });
</script>
