import { ref, computed, getCurrentScope, onScopeDispose, type Ref, type ComputedRef } from 'vue';
import type * as d3 from 'd3';

export interface ViewportBounds {
  /** Left edge of viewport in pixels */
  left: number;
  /** Right edge of viewport in pixels */
  right: number;
  /** Minimum visible date */
  minDate: Date;
  /** Maximum visible date */
  maxDate: Date;
  /** Top edge of viewport in pixels (row space) */
  top: number;
  /** Bottom edge of viewport in pixels (row space) */
  bottom: number;
}

export interface GanttViewportOptions {
  /** Buffer in pixels to add on each side of viewport (horizontal) */
  bufferPx?: number;
  /** Buffer in pixels to add above/below viewport (vertical) — rows are much
   * shorter than the horizontal day scale, so this defaults larger to still
   * cover a couple of screens' worth of rows. */
  verticalBufferPx?: number;
  /** Minimum horizontal scroll delta to trigger recalculation */
  scrollThreshold?: number;
  /**
   * Minimum vertical scroll delta to trigger recalculation (defaults to
   * `scrollThreshold` if unset). Every viewport change re-renders the whole
   * SVG, which is comparatively expensive, and that render competes for main-
   * thread time with the labels-column scroll sync — a low threshold here
   * makes that sync visibly stutter/lag during fast vertical scrolling. Kept
   * coarser than the horizontal threshold since `verticalBufferPx` is already
   * generous, so content stays pre-rendered well past this trigger point.
   */
  verticalScrollThreshold?: number;
  /**
   * Called when the viewport bounds actually change (scroll beyond threshold,
   * or a forced update). Wire re-rendering here instead of watching the culled
   * computeds: they depend on the time scale, which the render function itself
   * reassigns — watching them causes recursive updates.
   */
  onViewportChange?: () => void;
}

const DEFAULT_BUFFER_PX = 200;
const DEFAULT_VERTICAL_BUFFER_PX = 400;
const DEFAULT_SCROLL_THRESHOLD = 50;

export function useGanttViewport(
  scrollContainer: Ref<HTMLElement | null>,
  curX: Ref<d3.ScaleTime<number, number> | null>,
  options: GanttViewportOptions = {},
) {
  const bufferPx = options.bufferPx ?? DEFAULT_BUFFER_PX;
  const verticalBufferPx = options.verticalBufferPx ?? DEFAULT_VERTICAL_BUFFER_PX;
  const scrollThreshold = options.scrollThreshold ?? DEFAULT_SCROLL_THRESHOLD;
  const verticalScrollThreshold = options.verticalScrollThreshold ?? scrollThreshold;

  // Track viewport bounds
  const viewportLeft = ref(0);
  const viewportRight = ref(1000);
  const lastScrollLeft = ref(0);
  const viewportTop = ref(0);
  const viewportBottom = ref(1000);
  const lastScrollTop = ref(0);
  let rafId: number | null = null;
  let trackedContainer: HTMLElement | null = null;

  /**
   * Current viewport bounds with dates
   */
  const viewportBounds = computed<ViewportBounds | null>(() => {
    const x = curX.value;
    if (!x) return null;

    // Add buffer to prevent pop-in during scrolling
    const left = Math.max(0, viewportLeft.value - bufferPx);
    const right = viewportRight.value + bufferPx;
    const top = Math.max(0, viewportTop.value - verticalBufferPx);
    const bottom = viewportBottom.value + verticalBufferPx;

    return {
      left,
      right,
      minDate: x.invert(left),
      maxDate: x.invert(right),
      top,
      bottom,
    };
  });

  /**
   * Update viewport bounds based on current scroll position.
   * Returns true when the bounds actually changed (either axis).
   */
  function updateViewport(): boolean {
    const el = scrollContainer.value;
    if (!el) return false;

    const newLeft = el.scrollLeft;
    const newRight = el.scrollLeft + el.clientWidth;
    const newTop = el.scrollTop;
    const newBottom = el.scrollTop + el.clientHeight;

    const horizontalChanged = Math.abs(newLeft - lastScrollLeft.value) >= scrollThreshold;
    const verticalChanged = Math.abs(newTop - lastScrollTop.value) >= verticalScrollThreshold;

    if (!horizontalChanged && !verticalChanged) {
      return false;
    }

    viewportLeft.value = newLeft;
    viewportRight.value = newRight;
    lastScrollLeft.value = newLeft;
    viewportTop.value = newTop;
    viewportBottom.value = newBottom;
    lastScrollTop.value = newTop;

    return true;
  }

  /**
   * RAF-throttled scroll handler
   */
  function onViewportScroll() {
    if (rafId !== null) return;

    rafId = requestAnimationFrame(() => {
      rafId = null;
      if (updateViewport()) {
        options.onViewportChange?.();
      }
    });
  }

  /**
   * Force immediate viewport update (useful after render or resize)
   */
  function forceUpdate() {
    const el = scrollContainer.value;
    if (!el) return;

    viewportLeft.value = el.scrollLeft;
    viewportRight.value = el.scrollLeft + el.clientWidth;
    lastScrollLeft.value = el.scrollLeft;
    viewportTop.value = el.scrollTop;
    viewportBottom.value = el.scrollTop + el.clientHeight;
    lastScrollTop.value = el.scrollTop;
    options.onViewportChange?.();
  }

  /**
   * Create a computed that filters rows (or anything row-shaped) to the
   * vertical viewport. Generic over the row shape so it works for both
   * `LayoutRow` and any placeholder/skeleton row type.
   */
  function createVisibleRows<T extends { top: number; height: number }>(
    rows: ComputedRef<T[]> | (() => T[]),
  ): ComputedRef<T[]> {
    return computed(() => {
      const all = typeof rows === 'function' ? rows() : rows.value;
      const bounds = viewportBounds.value;
      if (!bounds) return all;

      return all.filter(r => r.top + r.height >= bounds.top && r.top <= bounds.bottom);
    });
  }

  /**
   * Attach scroll listener for viewport tracking
   * Returns cleanup function
   */
  function attachViewportTracking(): () => void {
    cleanup();
    const el = scrollContainer.value;
    if (!el) return () => {};

    trackedContainer = el;
    el.addEventListener('scroll', onViewportScroll, { passive: true });

    // Initial update
    forceUpdate();

    return () => {
      if (trackedContainer === el) {
        cleanup();
      }
    };
  }

  function cleanup() {
    trackedContainer?.removeEventListener('scroll', onViewportScroll);
    trackedContainer = null;
    if (rafId !== null) {
      cancelAnimationFrame(rafId);
      rafId = null;
    }
  }

  if (getCurrentScope()) {
    onScopeDispose(cleanup);
  }

  return {
    // State
    viewportBounds,
    viewportLeft,
    viewportRight,
    viewportTop,
    viewportBottom,

    // Methods
    updateViewport,
    forceUpdate,
    attachViewportTracking,
    cleanup,

    // Factory methods for filtered computeds
    createVisibleRows,
  };
}
