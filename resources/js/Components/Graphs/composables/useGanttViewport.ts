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
}

export interface GanttViewportOptions {
  /** Buffer in pixels to add on each side of viewport */
  bufferPx?: number;
  /** Minimum scroll delta to trigger recalculation */
  scrollThreshold?: number;
}

interface ParsedMeeting {
  id: string | number;
  date: Date;
  institution_id: string | number;
  [key: string]: any;
}

interface ParsedGap {
  institution_id: string | number;
  fromDate: Date;
  untilDate: Date;
  [key: string]: any;
}

interface ParsedDutyMember {
  institution_id: string | number;
  startDate: Date;
  endDate: Date | null;
  [key: string]: any;
}

const DEFAULT_BUFFER_PX = 200;
const DEFAULT_SCROLL_THRESHOLD = 50;

export function useGanttViewport(
  scrollContainer: Ref<HTMLElement | null>,
  curX: Ref<d3.ScaleTime<number, number> | null>,
  options: GanttViewportOptions = {},
) {
  const bufferPx = options.bufferPx ?? DEFAULT_BUFFER_PX;
  const scrollThreshold = options.scrollThreshold ?? DEFAULT_SCROLL_THRESHOLD;

  // Track viewport bounds
  const viewportLeft = ref(0);
  const viewportRight = ref(1000);
  const lastScrollLeft = ref(0);
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

    return {
      left,
      right,
      minDate: x.invert(left),
      maxDate: x.invert(right),
    };
  });

  /**
   * Update viewport bounds based on current scroll position
   */
  function updateViewport() {
    const el = scrollContainer.value;
    if (!el) return;

    const newLeft = el.scrollLeft;
    const newRight = el.scrollLeft + el.clientWidth;

    // Only update if scroll changed significantly
    if (Math.abs(newLeft - lastScrollLeft.value) >= scrollThreshold) {
      viewportLeft.value = newLeft;
      viewportRight.value = newRight;
      lastScrollLeft.value = newLeft;
    }
  }

  /**
   * RAF-throttled scroll handler
   */
  function onViewportScroll() {
    if (rafId !== null) return;

    rafId = requestAnimationFrame(() => {
      updateViewport();
      rafId = null;
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

    // Methods
    updateViewport,
    forceUpdate,
    attachViewportTracking,

    // Factory methods for filtered computeds
    createVisibleMeetings,
    createVisibleGaps,
    createVisibleDutyMembers,
  };
}
