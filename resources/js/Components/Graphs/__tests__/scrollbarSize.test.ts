import { afterEach, describe, expect, it, vi } from 'vitest';

import { horizontalScrollbarSize, resetScrollbarSizeCache } from '../scrollbarSize';

afterEach(() => {
  resetScrollbarSizeCache();
  vi.restoreAllMocks();
});

describe('horizontalScrollbarSize', () => {
  /**
   * jsdom lays nothing out, so both dimensions read 0 — the same answer overlay scrollbars
   * give, and the right one for a chart that must not reserve space it does not need.
   */
  it('reports no reserved strip where scrollbars take no space', () => {
    expect(horizontalScrollbarSize()).toBe(0);
  });

  it('measures once and reuses the answer', () => {
    const spy = vi.spyOn(document, 'createElement');

    horizontalScrollbarSize();
    horizontalScrollbarSize();

    expect(spy).toHaveBeenCalledTimes(1);
  });

  it('reports the strip a classic scrollbar takes out of the container', () => {
    // offsetHeight/clientHeight are the only two numbers the probe reads.
    vi.spyOn(HTMLElement.prototype, 'offsetHeight', 'get').mockReturnValue(100);
    vi.spyOn(HTMLElement.prototype, 'clientHeight', 'get').mockReturnValue(85);

    expect(horizontalScrollbarSize()).toBe(15);
  });

  it('leaves no probe behind in the document', () => {
    horizontalScrollbarSize();

    expect(document.body.children).toHaveLength(0);
  });
});
