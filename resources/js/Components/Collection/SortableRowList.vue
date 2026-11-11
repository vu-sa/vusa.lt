<template>
  <div ref="root" data-slot="sortable-row-list">
    <ul :class="rowListClass(frame)">
      <li
        v-for="(item, index) in items"
        :key="keyOf(item, index)"
        :data-key="keyOf(item, index)"
        :class="[ROW_ITEM_CLASS, 'items-center', rowMuted?.(item) && 'opacity-60']"
      >
        <button
          type="button"
          class="handle flex min-h-11 w-8 shrink-0 cursor-grab items-center justify-center self-stretch text-muted-foreground active:cursor-grabbing"
          :aria-label="$t('Tempti, kad pakeistum tvarką')"
          tabindex="-1"
          :disabled
        >
          <GripVertical class="size-4" aria-hidden="true" />
        </button>
        <div class="min-w-0 flex-1">
          <slot :item :index />
        </div>
        <div class="flex shrink-0 items-center pr-1">
          <Button type="button" variant="ghost" size="icon-sm" :disabled="disabled || index === 0" :aria-label="$t('Perkelti aukščiau')" :title="labelOf(item)" @click="move(index, -1)">
            <ArrowUp class="size-4" aria-hidden="true" />
          </Button>
          <Button type="button" variant="ghost" size="icon-sm" :disabled="disabled || index === items.length - 1" :aria-label="$t('Perkelti žemiau')" :title="labelOf(item)" @click="move(index, 1)">
            <ArrowDown class="size-4" aria-hidden="true" />
          </Button>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts" generic="T">
import { computed, ref, watch } from 'vue';
import { ArrowDown, ArrowUp, GripVertical } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import { useMotionAwareSortable } from '@/Composables/useMotionAwareSortable';

import { ROW_ITEM_CLASS, rowListClass, type RowListFrame } from './rowList';

/**
 * A row list in "change order" mode: drag by the handle or move with the arrows, so it works
 * by touch, keyboard and pointer alike. The parent keeps the draft and saves it.
 */
const items = defineModel<T[]>('items', { required: true });
const props = defineProps<{
  itemKey?: (item: T) => string | number;
  /** Names the row for the arrow buttons' tooltips. */
  itemLabel?: (item: T) => string;
  rowMuted?: (item: T) => boolean;
  frame?: RowListFrame;
  disabled?: boolean;
}>();

const root = ref<HTMLElement | null>(null);
const list = computed(() => root.value?.querySelector('ul') ?? null);
const sortableOptions = { handle: '.handle', forceFallback: true, animation: 100, watchElement: true, disabled: props.disabled };
const sortable = useMotionAwareSortable<T>(list, items, sortableOptions);
watch([() => props.disabled, list], () => sortable.option('disabled', Boolean(props.disabled)), { flush: 'post' });

const keyOf = (item: T, index: number) => props.itemKey?.(item) ?? index;
const labelOf = (item: T) => props.itemLabel?.(item);

function move(index: number, step: -1 | 1): void {
  const target = index + step;
  if (props.disabled || target < 0 || target >= items.value.length) return;
  const next = [...items.value];
  [next[index], next[target]] = [next[target]!, next[index]!];
  items.value = next;
}
</script>
