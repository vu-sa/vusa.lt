<template>
  <div ref="el" data-slot="sortable-duties">
    <CollectionRowList :items="contents" :item-key :row-muted :frame>
      <template #default="{ item, index }">
        <slot :item :model="item" :index />
      </template>
    </CollectionRowList>
  </div>
</template>

<script setup lang="ts" generic="T">
import { computed, ref } from 'vue';

import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import type { RowListFrame } from '@/Components/Collection/rowList';
import { useMotionAwareSortable } from '@/Composables/useMotionAwareSortable';

const contents = defineModel<T[]>('items', { default: () => [] });
defineProps<{
  itemKey?: (item: T) => string | number;
  rowMuted?: (item: T) => boolean;
  frame?: RowListFrame;
}>();
const el = ref<HTMLElement | null>(null);
const list = computed(() => el.value?.querySelector('ul') ?? null);
useMotionAwareSortable(list, contents, { handle: '.handle', forceFallback: true, animation: 100, watchElement: true });
</script>
