import { ref, shallowRef, watch } from 'vue';
import { unrefElement, useResizeObserver, type MaybeElement } from '@vueuse/core';

/**
 * Reports whether an element's content is actually clipped by `truncate` or
 * `line-clamp-*`.
 *
 * Table cells only earn a tooltip when it reveals text the reader cannot
 * already see; without this every cell would repeat its own visible value on
 * hover. Bind the returned `el` to the clipping element.
 *
 * jsdom implements neither `ResizeObserver` nor layout, so this always reports
 * `false` in component tests — assert the wiring rather than the measurement.
 */
export function useIsTruncated() {
  const el = shallowRef<MaybeElement>(null);
  const isTruncated = ref(false);

  const measure = () => {
    const node = unrefElement(el);

    // Between the two render branches the element is momentarily absent.
    // Reporting `false` there would swap the tooltip away and re-measure into
    // `true` forever, so hold the last known answer instead.
    if (!node) {
      return;
    }

    // Sub-pixel layout leaves untruncated text a fraction over its box, so
    // require a whole pixel of overflow before calling it clipped.
    isTruncated.value = node.scrollWidth - node.clientWidth > 1
      || node.scrollHeight - node.clientHeight > 1;
  };

  useResizeObserver(el, measure);
  watch(el, measure, { flush: 'post' });

  return { el, isTruncated, measure };
}
