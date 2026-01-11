/**
 * Gantt chart composables
 * 
 * These composables extract logic from MeetingsGantt.vue for better
 * maintainability and reusability.
 */

export { useGanttInteractions, type GanttInteractionOptions, type GanttInteractionRefs } from './useGanttInteractions'
export { useGanttViewport } from './useGanttViewport'
export { useGanttLayout, type GanttRow, type LayoutRow, type GanttLayoutOptions, type GanttLayoutData } from './useGanttLayout'
export { useGanttFiltering, type GanttFilteringOptions, type GanttFilteringData, type ParsedMeeting, type ParsedGap, type ParsedDutyMember, type ParsedInactivePeriod } from './useGanttFiltering'
export { useGanttLabels, type GanttLabelsOptions, type GanttLabelsData } from './useGanttLabels'
export { useColumnResize, type ColumnResizeOptions } from './useColumnResize'
