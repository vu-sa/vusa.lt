/**
 * Gantt Chart Color Configuration
 * 
 * Centralized color definitions for the MeetingsGantt component.
 * These colors are defined using OKLCH format for perceptual uniformity
 * and support both light and dark modes.
 * 
 * Colors are designed to be:
 * - Accessible with sufficient contrast
 * - Consistent with the application's design system
 * - Perceptually uniform across light/dark modes
 */

export interface GanttColors {
  // Meeting dots
  meetingComplete: string;
  meetingIncomplete: string;
  meetingNoItems: string;
  
  // Gap/Check-in lines
  gap: string;
  gapOpacity: number;
  
  // Safety bands around meetings
  safetyBandStart: string;
  safetyBandMid: string;
  safetyBandEnd: string;
  
  // Today line
  todayLine: string;
  
  // Hover effects
  hoverLine: string;
  hoverCircle: string;
  rowHover: string;
  
  // Vacation periods
  vacationSummer: string;
  vacationWinter: string;
  vacationEaster: string;
  vacationDefault: string;
  
  // Row backgrounds
  zebraEven: string;
  zebraOdd: string;
  tenantRow: string;
  
  // Grid and axis
  gridLine: string;
  yearMarker: string;
  axisText: string;
  
  // Tooltips
  tooltipBg: string;
  tooltipText: string;
  tooltipBorder: string;
}

/**
 * Light mode color palette
 * Uses softer, more muted tones for comfortable daytime viewing
 */
export const lightModeColors: GanttColors = {
  // Meeting dots - dark for visibility on light backgrounds
  meetingComplete: 'oklch(0.21 0.006 285.885)', // zinc-900
  meetingIncomplete: 'oklch(0.637 0.237 25.331)', // red-500
  meetingNoItems: 'oklch(0.637 0.237 25.331)', // red-500 (stroke only)
  
  // Gap lines - amber for attention without alarm
  gap: 'oklch(0.769 0.188 70.08 / 90%)', // amber-500/90%
  gapOpacity: 0.9,
  
  // Safety bands - subtle emerald gradient
  safetyBandStart: 'oklch(0.696 0.17 162.48 / 0%)', // emerald-500/0%
  safetyBandMid: 'oklch(0.696 0.17 162.48 / 18%)', // emerald-500/18%
  safetyBandEnd: 'oklch(0.696 0.17 162.48 / 0%)', // emerald-500/0%
  
  // Today line - prominent blue
  todayLine: 'oklch(0.623 0.214 259.815 / 80%)', // blue-500/80%
  
  // Hover effects
  hoverLine: 'oklch(0.21 0.006 285.885 / 25%)', // zinc-900/25%
  hoverCircle: 'oklch(0.623 0.214 259.815 / 90%)', // blue-500/90%
  rowHover: 'oklch(0.623 0.214 259.815 / 8%)', // blue-500/8%
  
  // Vacation periods - very subtle overlays
  vacationSummer: 'oklch(0.828 0.189 84.429 / 8%)', // amber-400/8%
  vacationWinter: 'oklch(0.809 0.105 251.813 / 8%)', // blue-300/8%
  vacationEaster: 'oklch(0.811 0.111 293.571 / 8%)', // violet-300/8%
  vacationDefault: 'oklch(0.707 0.022 261.325 / 8%)', // gray-400/8%
  
  // Row backgrounds
  zebraEven: 'oklch(0.21 0.006 285.885 / 2%)', // zinc-900/2%
  zebraOdd: 'transparent',
  tenantRow: 'oklch(0.968 0.007 247.896 / 70%)', // slate-50/70%
  
  // Grid and axis
  gridLine: 'oklch(0.552 0.016 285.938 / 10%)', // zinc-500/10%
  yearMarker: 'oklch(0.21 0.006 285.885 / 18%)', // zinc-900/18%
  axisText: 'oklch(0.552 0.016 285.938)', // zinc-500
  
  // Tooltips
  tooltipBg: 'oklch(1 0 0 / 95%)', // white/95%
  tooltipText: 'oklch(0.372 0.044 257.287)', // slate-700
  tooltipBorder: 'oklch(0.21 0.006 285.885 / 5%)', // zinc-900/5%
};

/**
 * Dark mode color palette
 * Uses lighter, more saturated colors for visibility on dark backgrounds
 */
export const darkModeColors: GanttColors = {
  // Meeting dots - light for visibility on dark backgrounds
  meetingComplete: 'oklch(0.985 0 0)', // white
  meetingIncomplete: 'oklch(0.712 0.194 13.428)', // rose-400
  meetingNoItems: 'oklch(0.712 0.194 13.428)', // rose-400 (stroke only)
  
  // Gap lines - slightly brighter amber
  gap: 'oklch(0.828 0.189 84.429 / 85%)', // amber-400/85%
  gapOpacity: 0.85,
  
  // Safety bands - slightly more visible emerald gradient
  safetyBandStart: 'oklch(0.765 0.177 163.223 / 0%)', // emerald-400/0%
  safetyBandMid: 'oklch(0.765 0.177 163.223 / 15%)', // emerald-400/15%
  safetyBandEnd: 'oklch(0.765 0.177 163.223 / 0%)', // emerald-400/0%
  
  // Today line - brighter blue
  todayLine: 'oklch(0.707 0.165 254.624 / 80%)', // blue-400/80%
  
  // Hover effects
  hoverLine: 'oklch(0.985 0 0 / 20%)', // white/20%
  hoverCircle: 'oklch(0.707 0.165 254.624 / 90%)', // blue-400/90%
  rowHover: 'oklch(0.707 0.165 254.624 / 10%)', // blue-400/10%
  
  // Vacation periods - subtle but visible overlays
  vacationSummer: 'oklch(0.828 0.189 84.429 / 6%)', // amber-400/6%
  vacationWinter: 'oklch(0.809 0.105 251.813 / 6%)', // blue-300/6%
  vacationEaster: 'oklch(0.811 0.111 293.571 / 6%)', // violet-300/6%
  vacationDefault: 'oklch(0.705 0.015 286.067 / 6%)', // zinc-400/6%
  
  // Row backgrounds
  zebraEven: 'oklch(0.985 0 0 / 3%)', // white/3%
  zebraOdd: 'transparent',
  tenantRow: 'oklch(0.274 0.006 286.033 / 70%)', // zinc-800/70%
  
  // Grid and axis
  gridLine: 'oklch(0.705 0.015 286.067 / 12%)', // zinc-400/12%
  yearMarker: 'oklch(0.985 0 0 / 15%)', // white/15%
  axisText: 'oklch(0.705 0.015 286.067)', // zinc-400
  
  // Tooltips
  tooltipBg: 'oklch(0.21 0.006 285.885 / 95%)', // zinc-900/95%
  tooltipText: 'oklch(0.929 0.013 255.508)', // slate-200
  tooltipBorder: 'oklch(0.985 0 0 / 10%)', // white/10%
};

/**
 * Get the appropriate color palette based on the current theme
 * 
 * @param isDarkMode - Whether dark mode is active
 * @returns The color palette for the current mode
 */
export function getGanttColors(isDarkMode: boolean): GanttColors {
  return isDarkMode ? darkModeColors : lightModeColors;
}

/**
 * Detect if dark mode is currently active
 * Checks for the 'dark' class on the document element
 * 
 * @returns true if dark mode is active
 */
export function isDarkModeActive(): boolean {
  if (typeof document === 'undefined') return false;
  return document.documentElement.classList.contains('dark');
}
