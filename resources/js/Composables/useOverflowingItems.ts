import { useResizeObserver } from '@vueuse/core';
import { nextTick, ref, watch, type Ref, type WatchSource } from 'vue';

/**
 * Indexes of the items in a clipped row whose right edge falls past the container, so a
 * "more" menu can list exactly what the row cannot show. jsdom has no layout (`clientWidth`
 * is 0), so nothing is ever flagged there.
 */
export function useOverflowingItems(container: Ref<HTMLElement | null>, items: WatchSource<unknown>) {
  const itemRefs = ref<(HTMLElement | null)[]>([]);
  const overflowIndexes = ref<Set<number>>(new Set());

  const setItemRef = (el: unknown, index: number) => {
    itemRefs.value[index] = el instanceof HTMLElement ? el : null;
  };

  const measure = () => {
    const element = container.value;

    if (!element || element.clientWidth === 0) {
      return;
    }

    const containerRight = element.getBoundingClientRect().left + element.clientWidth;
    const next = new Set<number>();

    itemRefs.value.forEach((item, index) => {
      if (item && item.getBoundingClientRect().right > containerRight) {
        next.add(index);
      }
    });

    overflowIndexes.value = next;
  };

  useResizeObserver(container, measure);
  watch(items, () => nextTick(measure), { immediate: true, flush: 'post' });

  return { overflowIndexes, setItemRef, measure };
}
