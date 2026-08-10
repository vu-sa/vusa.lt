import { describe, it, expect } from 'vitest';

import { useIsTruncated } from '@/Composables/useIsTruncated';

/**
 * jsdom reports every layout box as zero-sized, so these tests drive `measure()`
 * against elements with stubbed metrics rather than real layout.
 */
const elementWith = (metrics: Partial<Record<'scrollWidth' | 'clientWidth' | 'scrollHeight' | 'clientHeight', number>>) => {
  const node = document.createElement('span');

  for (const [key, value] of Object.entries(metrics)) {
    Object.defineProperty(node, key, { value, configurable: true });
  }

  return node;
};

describe('useIsTruncated', () => {
  it('reports untruncated content as not clipped', () => {
    const { el, isTruncated, measure } = useIsTruncated();

    el.value = elementWith({ scrollWidth: 100, clientWidth: 100, scrollHeight: 20, clientHeight: 20 });
    measure();

    expect(isTruncated.value).toBe(false);
  });

  it('detects horizontal clipping', () => {
    const { el, isTruncated, measure } = useIsTruncated();

    el.value = elementWith({ scrollWidth: 240, clientWidth: 100, scrollHeight: 20, clientHeight: 20 });
    measure();

    expect(isTruncated.value).toBe(true);
  });

  it('detects vertical clipping from line-clamp', () => {
    const { el, isTruncated, measure } = useIsTruncated();

    el.value = elementWith({ scrollWidth: 100, clientWidth: 100, scrollHeight: 72, clientHeight: 40 });
    measure();

    expect(isTruncated.value).toBe(true);
  });

  it('ignores sub-pixel overflow', () => {
    const { el, isTruncated, measure } = useIsTruncated();

    el.value = elementWith({ scrollWidth: 100.6, clientWidth: 100, scrollHeight: 20, clientHeight: 20 });
    measure();

    expect(isTruncated.value).toBe(false);
  });

  // Swapping the tooltip in and out detaches the element for a tick. Resetting
  // to false there would swap the tooltip back out and re-measure forever.
  it('holds its last answer while the element is detached', () => {
    const { el, isTruncated, measure } = useIsTruncated();

    el.value = elementWith({ scrollWidth: 240, clientWidth: 100, scrollHeight: 20, clientHeight: 20 });
    measure();
    expect(isTruncated.value).toBe(true);

    el.value = null;
    measure();

    expect(isTruncated.value).toBe(true);
  });
});
