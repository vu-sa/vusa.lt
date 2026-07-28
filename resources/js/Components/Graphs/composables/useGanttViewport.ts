/**
 * useGanttViewport - Horizontal viewport culling for Gantt chart performance
 *
 * This composable implements viewport-based rendering optimization by tracking
 * which portion of the timeline is currently visible and filtering data to only
 * include elements within the visible range (plus a buffer).
 *
 * This significantly improves rendering performance when dealing with large
 * date ranges or many meetings/gaps, as D3 only needs to render visible elements.
 *
 * Performance characteristics:
 * - Uses RAF-throttled scroll tracking (not debounced) for smooth updates
 * - Adds configurable buffer zones to prevent pop-in during scrolling
 * - Only recalculates when scroll position changes significantly
 */
import { ref, computed, onUnmounted, type Ref, type ComputedRef } from 'vue';
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

interface ParsedMeeting {
  id: string | number;
  date: Date;
  institution_id: string | number;
  [key: string]: unknown;
}

interface ParsedGap {
  institution_id: string | number;
  fromDate: Date;
  untilDate: Date;
  [key: string]: unknown;
}

interface ParsedDutyMember {
  institution_id: string | number;
  startDate: Date;
  endDate: Date | null;
  [key: string]: unknown;
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
   * Create a computed that filters meetings to visible viewport
   */
  function createVisibleMeetings(
    parsedMeetings: ComputedRef<ParsedMeeting[]>,
  ): ComputedRef<ParsedMeeting[]> {
    return computed(() => {
      const bounds = viewportBounds.value;
      if (!bounds) return parsedMeetings.value;

      return parsedMeetings.value.filter((m) => {
        return m.date >= bounds.minDate && m.date <= bounds.maxDate;
      });
    });
  }

  /**
   * Create a computed that filters gaps to visible viewport
   */
  function createVisibleGaps(
    parsedGaps: ComputedRef<ParsedGap[]>,
  ): ComputedRef<ParsedGap[]> {
    return computed(() => {
      const bounds = viewportBounds.value;
      if (!bounds) return parsedGaps.value;

      return parsedGaps.value.filter((g) => {
        // Gap is visible if any part of it overlaps with viewport
        return g.untilDate >= bounds.minDate && g.fromDate <= bounds.maxDate;
      });
    });
  }

  /**
   * Create a computed that filters duty members to visible viewport
   */
  function createVisibleDutyMembers(
    parsedDutyMembers: ComputedRef<ParsedDutyMember[]>,
  ): ComputedRef<ParsedDutyMember[]> {
    return computed(() => {
      const bounds = viewportBounds.value;
      if (!bounds) return parsedDutyMembers.value;

      return parsedDutyMembers.value.filter((m) => {
        // Member is visible if start date is within viewport
        // (endDate might be null for current members)
        const endDate = m.endDate ?? new Date(2099, 11, 31);
        return endDate >= bounds.minDate && m.startDate <= bounds.maxDate;
      });
    });
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
    const el = scrollContainer.value;
    if (!el) return () => {};

    el.addEventListener('scroll', onViewportScroll, { passive: true });

    // Initial update
    forceUpdate();

    return () => {
      el.removeEventListener('scroll', onViewportScroll);
      if (rafId !== null) {
        cancelAnimationFrame(rafId);
        rafId = null;
      }
    };
  }

  // Cleanup on unmount
  onUnmounted(() => {
    if (rafId !== null) {
      cancelAnimationFrame(rafId);
    }
  });

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

    // Factory methods for filtered computeds
    createVisibleMeetings,
    createVisibleGaps,
    createVisibleDutyMembers,
    createVisibleRows,
  };
}
