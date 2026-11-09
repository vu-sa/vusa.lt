<template>
  <ul :class="rowListClass(frame)" data-slot="collection-row-list">
    <li
      v-for="(item, index) in items"
      :key="keyOf(item, index)"
      :class="[ROW_ITEM_CLASS, rowMuted?.(item) && 'opacity-60']"
    >
      <div class="min-w-0 flex-1">
        <slot :item :index />
      </div>
    </li>
  </ul>
</template>

<script setup lang="ts" generic="T">
import { ROW_ITEM_CLASS, rowListClass, type RowListFrame } from './rowList';

/** A short list inside a record section, styled like a collection's rows view. */
const props = defineProps<{
  items: readonly T[];
  itemKey?: (item: T) => string | number;
  rowMuted?: (item: T) => boolean;
  /** `attached` right under a section header, whose rule closes the box; `box` under a subheading. */
  frame?: RowListFrame;
}>();

const keyOf = (item: T, index: number) => props.itemKey?.(item) ?? index;
</script>
