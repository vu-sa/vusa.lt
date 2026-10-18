/**
 * Timeline range, infinite scroll, zoom and keyboard navigation for the meetings Gantt.
 *
 * The chart renders on the next animation frame, so anything that must read the new scale
 * (keeping the viewport anchored after a zoom or a leftward extension) goes through `afterRender`.
 */
import { ref, computed, type Ref } from 'vue';
import * as d3 from 'd3';

import { snapToZoomStop, stepZoomStop } from '../ganttZoom';

export interface GanttInteractionOptions {
  daysBefore: number;
  daysAfter: number;
  infiniteScroll: boolean;
  /** Distance from an edge (px) at which the range extends */
  extendThresholdPx: number;
  /** Smallest extension in days; at low zoom one step is a whole viewport instead */
  extendStepDays: number;
  /** Persisted center date to restore */
  centerDateTimestamp?: Ref<number | null>;
}

export interface GanttInteractionRefs {
  rightScroll: Ref<HTMLElement | null>;
  leftLabels: Ref<HTMLElement | null>;
  /** Current time scale (set by render) */
  curX: Ref<d3.ScaleTime<number, number> | null>;
  dayWidthPx: Ref<number>;
}

export interface GanttInteractionCallbacks {
  /** Run `callback` once the chart has rendered with the current range and zoom */
  afterRender: (callback: () => void) => void;
}

/** Days kept beyond the viewport on each side, so scrolling a screen never reaches an edge. */
const COVERAGE_BUFFER_DAYS = 30;

function today(): Date {
  const d = new Date();
  d.setHours(0, 0, 0, 0);
  return d;
}

export function useGanttInteractions(
  options: GanttInteractionOptions,
  refs: GanttInteractionRefs,
  callbacks: GanttInteractionCallbacks,
) {
  const { dayWidthPx } = refs;
  const extraBefore = ref(0);
  const extraAfter = ref(0);
  let extending = false;
  let didInitialScroll = false;

  const baseMin = () => d3.timeDay.offset(today(), -options.daysBefore);
  const baseMax = () => d3.timeDay.offset(today(), options.daysAfter);

  const minTime = computed(() => d3.timeDay.offset(baseMin(), -extraBefore.value));
  const maxTime = computed(() => d3.timeDay.offset(baseMax(), extraAfter.value));

  const viewportDays = (width = dayWidthPx.value) => Math.ceil((refs.rightScroll.value?.clientWidth ?? 1200) / width);

  /** Widen the range so it spans the viewport plus one more viewport on each side of `anchor`. */
  function ensureCoverage(anchor: Date, width = dayWidthPx.value): void {
    const reach = Math.ceil(viewportDays(width) * 1.5) + COVERAGE_BUFFER_DAYS;
    const neededMin = d3.timeDay.offset(anchor, -reach);
    const neededMax = d3.timeDay.offset(anchor, reach);

    if (neededMin < baseMin()) {
      extraBefore.value = Math.max(extraBefore.value, d3.timeDay.count(neededMin, baseMin()));
    }
    if (neededMax > baseMax()) {
      extraAfter.value = Math.max(extraAfter.value, d3.timeDay.count(baseMax(), neededMax));
    }
  }

  function storedCenter(): Date | null {
    const timestamp = options.centerDateTimestamp?.value;
    return timestamp ? new Date(timestamp) : null;
  }

  /** Call before the first render. */
  function applyInitialExtension(): void {
    ensureCoverage(storedCenter() ?? today());
  }

  /** Center the stored date (or today) once, after the first render with a measurable viewport. */
  function applyInitialScrollPosition(x: d3.ScaleTime<number, number>, marginLeft = 0): void {
    const el = refs.rightScroll.value;
    if (!el || didInitialScroll || el.clientWidth <= 0) return;

    const [domainMin, domainMax] = x.domain() as [Date, Date];
    const target = Math.max(domainMin.getTime(), Math.min(domainMax.getTime(), (storedCenter() ?? new Date()).getTime()));
    el.scrollLeft = Math.max(0, x(new Date(target)) + marginLeft - el.clientWidth / 2);
    didInitialScroll = true;
  }

  function centerDate(): Date | null {
    const el = refs.rightScroll.value;
    const x = refs.curX.value;
    return el && x ? x.invert(el.scrollLeft + el.clientWidth / 2) : null;
  }

  function scrollDateToCenter(date: Date): void {
    const el = refs.rightScroll.value;
    const x = refs.curX.value;
    if (el && x) {
      el.scrollLeft = Math.max(0, x(date) - el.clientWidth / 2);
    }
  }

  function onScroll(): void {
    if (!options.infiniteScroll || extending) return;
    const el = refs.rightScroll.value;
    if (!el) return;

    const step = Math.max(options.extendStepDays, viewportDays());
    const atLeft = el.scrollLeft <= options.extendThresholdPx;
    const atRight = el.scrollLeft + el.clientWidth >= el.scrollWidth - options.extendThresholdPx;

    if (!atLeft && !atRight) return;

    extending = true;
    if (atLeft) {
      const anchor = centerDate();
      extraBefore.value += step;
      callbacks.afterRender(() => {
        if (anchor) {
          scrollDateToCenter(anchor);
        }
        extending = false;
      });
    }
    else {
      extraAfter.value += step;
      callbacks.afterRender(() => {
        extending = false;
      });
    }
  }

  function onScaleChange(width: number): void {
    const newWidth = snapToZoomStop(width);
    if (newWidth === dayWidthPx.value) return;

    const anchor = centerDate() ?? today();
    ensureCoverage(anchor, newWidth);
    dayWidthPx.value = newWidth;
    callbacks.afterRender(() => scrollDateToCenter(anchor));
  }

  function navigateToToday(): void {
    const target = today();
    ensureCoverage(target);
    callbacks.afterRender(() => scrollDateToCenter(target));
  }

  function navigateDays(days: number): void {
    const el = refs.rightScroll.value;
    if (el) {
      el.scrollLeft = Math.max(0, el.scrollLeft + days * dayWidthPx.value);
    }
  }

  function scrollVertically(pixels: number): void {
    const el = refs.rightScroll.value;
    if (el) {
      el.scrollTop = Math.max(0, el.scrollTop + pixels);
    }
  }

  /**
   * The labels column has no scrollbar of its own outside focus mode, so it follows the timeline
   * by transform; in focus mode both scroll and each follows the other.
   */
  function setupVerticalScrollSync(isFullscreen: boolean): (() => void) | undefined {
    const timelineScroll = refs.rightScroll.value;
    const labelsContainer = refs.leftLabels.value;
    if (!timelineScroll || !labelsContainer) return undefined;

    let isSyncing = false;
    const release = () => requestAnimationFrame(() => {
      isSyncing = false;
    });

    const syncFromTimeline = () => {
      if (isSyncing) return;
      isSyncing = true;
      if (isFullscreen) {
        labelsContainer.scrollTop = timelineScroll.scrollTop;
      }
      else {
        const labelsContent = labelsContainer.querySelector<HTMLElement>('.grid');
        if (labelsContent) {
          labelsContent.style.transform = `translateY(-${timelineScroll.scrollTop}px)`;
        }
      }
      release();
    };

    const syncFromLabels = () => {
      if (isSyncing || !isFullscreen) return;
      isSyncing = true;
      timelineScroll.scrollTop = labelsContainer.scrollTop;
      release();
    };

    timelineScroll.addEventListener('scroll', syncFromTimeline, { passive: true });
    labelsContainer.addEventListener('scroll', syncFromLabels, { passive: true });

    return () => {
      timelineScroll.removeEventListener('scroll', syncFromTimeline);
      labelsContainer.removeEventListener('scroll', syncFromLabels);
    };
  }

  function attachScrollHandler(): (() => void) | undefined {
    const el = refs.rightScroll.value;
    if (!el || !options.infiniteScroll) return undefined;

    el.addEventListener('scroll', onScroll, { passive: true });
    return () => el.removeEventListener('scroll', onScroll);
  }

  /**
   * Arrows move a week (Shift: a month) or scroll rows; +/- zoom one stop; Home goes to today;
   * Page Up/Down move three months. Active while focus is inside the chart, or on the body with the pointer over it.
   */
  function attachKeyboardHandler(containerEl: HTMLElement | null): (() => void) | undefined {
    if (!containerEl) return undefined;

    let isHovering = false;
    const handleMouseEnter = () => {
      isHovering = true;
    };
    const handleMouseLeave = () => {
      isHovering = false;
    };
    containerEl.addEventListener('mouseenter', handleMouseEnter);
    containerEl.addEventListener('mouseleave', handleMouseLeave);

    const handleKeyDown = (event: KeyboardEvent) => {
      const focusInContainer = containerEl.contains(document.activeElement);
      const focusOnBodyAndHovering = document.activeElement === document.body && isHovering;
      if (!focusInContainer && !focusOnBodyAndHovering) return;

      const target = event.target as HTMLElement;
      if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable) return;

      const actions: Record<string, () => void> = {
        'ArrowLeft': () => navigateDays(event.shiftKey ? -30 : -7),
        'ArrowRight': () => navigateDays(event.shiftKey ? 30 : 7),
        'ArrowUp': () => scrollVertically(event.shiftKey ? -300 : -100),
        'ArrowDown': () => scrollVertically(event.shiftKey ? 300 : 100),
        '+': () => onScaleChange(stepZoomStop(dayWidthPx.value, 1)),
        '=': () => onScaleChange(stepZoomStop(dayWidthPx.value, 1)),
        '-': () => onScaleChange(stepZoomStop(dayWidthPx.value, -1)),
        '_': () => onScaleChange(stepZoomStop(dayWidthPx.value, -1)),
        'Home': navigateToToday,
        'PageUp': () => navigateDays(-90),
        'PageDown': () => navigateDays(90),
      };

      const action = actions[event.key];
      if (action) {
        event.preventDefault();
        action();
      }
    };

    document.addEventListener('keydown', handleKeyDown);

    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      containerEl.removeEventListener('mouseenter', handleMouseEnter);
      containerEl.removeEventListener('mouseleave', handleMouseLeave);
    };
  }

  return {
    extraBefore,
    extraAfter,
    minTime,
    maxTime,
    ensureCoverage,
    applyInitialExtension,
    applyInitialScrollPosition,
    onScroll,
    onScaleChange,
    navigateToToday,
    setupVerticalScrollSync,
    attachScrollHandler,
    attachKeyboardHandler,
  };
}
