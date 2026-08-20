/**
 * Month-column width in pixels. The zoom unit is the month, not the day.
 *
 * The maximum is deliberately modest: past ~100px a column is wider than any label it
 * carries, and the chart stops being a chart.
 */
export const MIN_MONTH_WIDTH = 24;
export const MAX_MONTH_WIDTH = 100;
export const DEFAULT_MONTH_WIDTH = 64;

/** Three tiers: years, cadence names, month abbreviations. */
export const TIMELINE_HEADER_HEIGHT = 46;

/** How far a drag must travel before it counts as one rather than a click. */
export const DRAG_THRESHOLD_PX = 10;

/** How close an edge drag must land to a boundary before it snaps to it. */
export const SNAP_PX = 10;

/** Width of the invisible resize handle at each end of an editable bar. */
export const HANDLE_WIDTH_PX = 6;

/**
 * Mirrors PlanDutiableTimelineChanges::DEFAULT_ALIGN_THRESHOLD_DAYS — past this an
 * off-cadence date reads as deliberate, and a bulk align leaves it alone.
 */
export const DEFAULT_ALIGN_THRESHOLD_DAYS = 45;

/** Where the persisted view settings live. Shared by every scope, like the meetings chart. */
export const VIEW_STORAGE_KEY = 'dutiable-timeline-view';

/** Below this a month column cannot fit a three-letter name, so only every Nth is labelled. */
export const MONTH_LABEL_MIN_WIDTH = 34;
