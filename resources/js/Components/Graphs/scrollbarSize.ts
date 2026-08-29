let cached: number | null = null;

/**
 * How many pixels a classic horizontal scrollbar eats out of a scroll container's height.
 *
 * A chart that sizes its box to `header + rows` is short by exactly this much, so the
 * bottom lane ends up under the scrollbar and a vertical scrollbar appears to cover the
 * difference. Overlay scrollbars (macOS, and jsdom) measure 0, which is the right answer
 * for them — they float over the content rather than taking space from it.
 *
 * Measured once: the value cannot change without a browser restart.
 */
export function horizontalScrollbarSize(): number {
  if (cached !== null) return cached;

  if (typeof document === 'undefined' || !document.body) return 0;

  const probe = document.createElement('div');
  probe.style.cssText = 'position:absolute;top:-9999px;width:100px;height:100px;overflow:scroll;';
  document.body.appendChild(probe);
  cached = probe.offsetHeight - probe.clientHeight;
  probe.remove();

  return cached;
}

/** Test seam — the probe is memoised, so a suite that fakes layout must be able to clear it. */
export function resetScrollbarSizeCache(): void {
  cached = null;
}
