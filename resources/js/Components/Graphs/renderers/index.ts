/**
 * Gantt chart D3 rendering utilities
 * 
 * These modules extract D3 rendering logic from MeetingsGantt.vue for better
 * maintainability and testability. Each renderer is a pure function that
 * receives all necessary context and performs DOM mutations via D3.
 */

export { renderBackground, type BackgroundRenderContext } from './renderBackground'
export { renderAxis, type AxisRenderContext } from './renderAxis'
export { renderVacations, type VacationRenderContext } from './renderVacations'
export { renderMeetings, type MeetingRenderContext } from './renderMeetings'
export { renderGaps, type GapRenderContext } from './renderGaps'
export { renderDutyMembers, type DutyMemberRenderContext } from './renderDutyMembers'
export { renderInactivePeriods, type InactivePeriodsRenderContext } from './renderInactivePeriods'
export { renderTodayLine, type TodayLineRenderContext } from './renderTodayLine'
export { renderHoverEffects, type HoverEffectsRenderContext } from './renderHoverEffects'
export { setupDefs, type DefsContext } from './setupDefs'
export { 
  createGanttTooltip, 
  type GanttTooltipManager, 
  type TooltipContent, 
  type TooltipContentType 
} from './GanttTooltip'
export { createCenterLine, type CenterLineManager, type CenterLineRenderContext } from './renderCenterLine'
