<template>
  <div
    ref="containerRef"
    data-reka-virtualizer=""
    :style="{
      position: 'relative',
      width: '100%',
      height: `${virtualizer.getTotalSize()}px`,
    }"
  >
    <template v-for="virtualItem in virtualizer.getVirtualItems()" :key="virtualItem.key">
      <slot
        :option="options[virtualItem.index]"
        :virtual-item
        :virtual-index="virtualItem.index"
        :style="{
          position: 'absolute',
          top: 0,
          left: 0,
          width: '100%',
          transform: `translateY(${virtualItem.start}px)`,
        }"
      />
    </template>
  </div>
</template>

<script setup lang="ts" generic="T">
import { useVirtualizer } from '@tanstack/vue-virtual';
import { computed, onMounted, shallowRef, useTemplateRef } from 'vue';

const props = withDefaults(defineProps<{
  options: T[];
  estimateSize?: number | ((index: number) => number);
  overscan?: number;
}>(), {
  estimateSize: 32,
  overscan: 12,
});

defineSlots<{
  default(props: {
    option: T;
    virtualItem: ReturnType<ReturnType<typeof useVirtualizer>['value']['getVirtualItems']>[number];
    virtualIndex: number;
    style: Record<string, string>;
  }): unknown;
}>();

const containerRef = useTemplateRef<HTMLElement>('containerRef');
const scrollEl = shallowRef<HTMLElement | null>(null);

// The parent element is SelectViewport — available synchronously in onMounted
// because SelectViewport mounts before its children
onMounted(() => {
  scrollEl.value = containerRef.value?.parentElement ?? null;
});

const virtualizer = useVirtualizer(computed(() => ({
  count: props.options.length,
  estimateSize: (index: number) =>
    typeof props.estimateSize === 'function' ? props.estimateSize(index) : props.estimateSize,
  getScrollElement: () => scrollEl.value,
  overscan: props.overscan,
})));
</script>
