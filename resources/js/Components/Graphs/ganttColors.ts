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
  // Meeting dots - status-based colors
  meetingComplete: string;
  meetingIncomplete: string;
  meetingNoItems: string;
  meetingUnauthorized: string;
  // Meeting alignment colors (for complete meetings)
  meetingAligned: string; // All votes aligned with student position
  meetingMixed: string; // Some aligned, some not
  meetingMisaligned: string; // None aligned

  // Check-in visualization
  gap: string;
  gapOpacity: number;
  checkInFill: string;
  checkInStroke: string;
  checkInIcon: string;
  checkInIconOpacity: number;

  // Safety bands around meetings
  safetyBandStart: string;
  safetyBandMid: string;
  safetyBandEnd: string;
  safetyBandFlat: string;
  safetyBandStroke: string;

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
  vacationSummerStroke: string;
  vacationWinterStroke: string;
  vacationEasterStroke: string;
  vacationDefaultStroke: string;
  vacationSolidBg: string;

  // Row backgrounds
  zebraEven: string;
  zebraOdd: string;
  tenantRow: string;

  // Grid and axis
  gridLine: string;
  sundayMark: string;
  yearMarker: string;
  axisText: string;

  // Tooltips
  tooltipBg: string;
  tooltipText: string;
  tooltipBorder: string;

  // Center line indicator
  centerLine: string;
  centerDateBg: string;
  centerDateText: string;
  centerDateBorder: string;

  // Month headers
  monthHeaderBg: string;
  monthHeaderText: string;

  // Axis background (solid, for sticky header)
  axisBg: string;
}

/**
 * Light mode color palette
 * Uses softer, more muted tones for comfortable daytime viewing
 */
export const lightModeColors: GanttColors = {
  // Meeting dots - dark for visibility on light backgrounds
  meetingComplete: 'oklch(0.21 0.006 285.885)', // zinc-900
  meetingIncomplete: 'oklch(0.769 0.188 70.08)', // amber-500 (softer than red for incomplete)
  meetingNoItems: 'oklch(0.552 0.016 285.938)', // zinc-500 (neutral for empty)
  meetingUnauthorized: 'oklch(0.552 0.016 285.938)', // zinc-500 - grey for unauthorized
  // Meeting alignment colors
  meetingAligned: 'oklch(0.527 0.154 150.069)', // emerald-600 - all aligned
  meetingMixed: 'oklch(0.666 0.179 58.318)', // amber-600 - mixed alignment
  meetingMisaligned: 'oklch(0.577 0.245 27.325)', // red-600 - all misaligned

  // Check-in visualization - amber theme
  gap: 'oklch(0.769 0.188 70.08 / 90%)', // amber-500/90% (legacy, kept for compatibility)
  gapOpacity: 0.9,
  checkInFill: 'oklch(0.828 0.189 84.429 / 12%)', // amber-400/12% - subtle fill for striped pattern
  checkInStroke: 'oklch(0.769 0.188 70.08 / 40%)', // amber-500/40% - border stroke
  checkInIcon: 'oklch(0.666 0.179 58.318)', // amber-600 - icon fill
  checkInIconOpacity: 0.85,

  // Safety bands - subtle emerald with border
  safetyBandStart: 'oklch(0.696 0.17 162.48 / 0%)', // emerald-500/0%
  safetyBandMid: 'oklch(0.696 0.17 162.48 / 18%)', // emerald-500/18%
  safetyBandEnd: 'oklch(0.696 0.17 162.48 / 0%)', // emerald-500/0%
  safetyBandFlat: 'oklch(0.696 0.17 162.48 / 12%)', // emerald-500/12% - flat low-opacity green
  safetyBandStroke: 'oklch(0.696 0.17 162.48 / 35%)', // emerald-500/35% - border stroke

  // Today line - prominent blue
  todayLine: 'oklch(0.623 0.214 259.815 / 80%)', // blue-500/80%

  // Hover effects
  hoverLine: 'oklch(0.21 0.006 285.885 / 25%)', // zinc-900/25%
  hoverCircle: 'oklch(0.623 0.214 259.815 / 90%)', // blue-500/90%
  rowHover: 'oklch(0.623 0.214 259.815 / 8%)', // blue-500/8%

  // Vacation periods - subtle overlays with borders
  vacationSummer: 'oklch(0.828 0.189 84.429 / 8%)', // amber-400/8%
  vacationWinter: 'oklch(0.809 0.105 251.813 / 8%)', // blue-300/8%
  vacationEaster: 'oklch(0.811 0.111 293.571 / 8%)', // violet-300/8%
  vacationDefault: 'oklch(0.707 0.022 261.325 / 8%)', // gray-400/8%
  vacationSummerStroke: 'oklch(0.828 0.189 84.429 / 25%)', // amber-400/25%
  vacationWinterStroke: 'oklch(0.809 0.105 251.813 / 25%)', // blue-300/25%
  vacationEasterStroke: 'oklch(0.811 0.111 293.571 / 25%)', // violet-300/25%
  vacationDefaultStroke: 'oklch(0.707 0.022 261.325 / 25%)', // gray-400/25%
  vacationSolidBg: 'oklch(1 0 0)', // white - solid background to cover weekends

  // Row backgrounds - very subtle to not compete with weekend bands
  zebraEven: 'oklch(0.21 0.006 285.885 / 1%)', // zinc-900/1%
  zebraOdd: 'transparent',
  tenantRow: 'oklch(0.968 0.007 247.896 / 70%)', // slate-50/70%

  // Grid and axis
  gridLine: 'oklch(0.552 0.016 285.938 / 10%)', // zinc-500/10%
  sundayMark: 'oklch(0.552 0.016 285.938 / 6%)', // zinc-500/6% - subtle Sunday markers
  yearMarker: 'oklch(0.21 0.006 285.885 / 18%)', // zinc-900/18%
  axisText: 'oklch(0.552 0.016 285.938)', // zinc-500

  // Tooltips
  tooltipBg: 'oklch(1 0 0 / 95%)', // white/95%
  tooltipText: 'oklch(0.372 0.044 257.287)', // slate-700
  tooltipBorder: 'oklch(0.21 0.006 285.885 / 5%)', // zinc-900/5%

  // Center line indicator
  centerLine: 'oklch(0.637 0.237 25.331 / 60%)', // red-500/60%
  centerDateBg: 'oklch(1 0 0 / 90%)', // white/90%
  centerDateText: 'oklch(0.372 0.044 257.287)', // slate-700
  centerDateBorder: 'oklch(0.21 0.006 285.885 / 15%)', // zinc-900/15%

  // Month headers
  monthHeaderBg: 'oklch(0.968 0.007 247.896 / 90%)', // slate-50/90%
  monthHeaderText: 'oklch(0.446 0.043 257.281)', // slate-600

  // Axis background (solid, for sticky header)
  axisBg: 'oklch(1 0 0)', // white
};

/**
 * Dark mode color palette
 * Uses lighter, more saturated colors for visibility on dark backgrounds
 */
export const darkModeColors: GanttColors = {
  // Meeting dots - light for visibility on dark backgrounds
  meetingComplete: 'oklch(0.985 0 0)', // white
  meetingIncomplete: 'oklch(0.828 0.189 84.429)', // amber-400 (softer for incomplete)
  meetingNoItems: 'oklch(0.705 0.015 286.067)', // zinc-400 (neutral for empty)
  meetingUnauthorized: 'oklch(0.705 0.015 286.067)', // zinc-400 - grey for unauthorized
  // Meeting alignment colors
  meetingAligned: 'oklch(0.765 0.177 163.223)', // emerald-400 - all aligned
  meetingMixed: 'oklch(0.828 0.189 84.429)', // amber-400 - mixed alignment
  meetingMisaligned: 'oklch(0.712 0.194 13.428)', // rose-400 - all misaligned

  // Check-in visualization - amber theme (dimmed for dark mode)
  gap: 'oklch(0.75 0.14 84.429 / 70%)', // amber-400 dimmed/70% (legacy, kept for compatibility)
  gapOpacity: 0.7,
  checkInFill: 'oklch(0.75 0.14 84.429 / 8%)', // amber-400/8% - subtle fill for striped pattern
  checkInStroke: 'oklch(0.75 0.14 84.429 / 30%)', // amber-400/30% - border stroke
  checkInIcon: 'oklch(0.828 0.189 84.429)', // amber-400 - icon fill
  checkInIconOpacity: 0.75,

  // Safety bands - subtle emerald with border (reduced brightness)
  safetyBandStart: 'oklch(0.65 0.12 163.223 / 0%)', // emerald dimmed/0%
  safetyBandMid: 'oklch(0.65 0.12 163.223 / 8%)', // emerald dimmed/8%
  safetyBandEnd: 'oklch(0.65 0.12 163.223 / 0%)', // emerald dimmed/0%
  safetyBandFlat: 'oklch(0.65 0.12 163.223 / 6%)', // emerald dimmed/6% - flat low-opacity green
  safetyBandStroke: 'oklch(0.65 0.12 163.223 / 25%)', // emerald dimmed/25% - border stroke

  // Today line - subtle blue
  todayLine: 'oklch(0.65 0.12 254.624 / 60%)', // blue-400 dimmed/60%

  // Hover effects
  hoverLine: 'oklch(0.985 0 0 / 15%)', // white/15%
  hoverCircle: 'oklch(0.65 0.12 254.624 / 80%)', // blue-400 dimmed/80%
  rowHover: 'oklch(0.65 0.12 254.624 / 6%)', // blue-400 dimmed/6%

  // Vacation periods - subtle overlays with borders (reduced brightness)
  vacationSummer: 'oklch(0.75 0.14 84.429 / 4%)', // amber-400 dimmed/4%
  vacationWinter: 'oklch(0.72 0.08 251.813 / 4%)', // blue-300 dimmed/4%
  vacationEaster: 'oklch(0.72 0.08 293.571 / 4%)', // violet-300 dimmed/4%
  vacationDefault: 'oklch(0.65 0.01 286.067 / 4%)', // zinc-400 dimmed/4%
  vacationSummerStroke: 'oklch(0.75 0.14 84.429 / 18%)', // amber-400/18%
  vacationWinterStroke: 'oklch(0.72 0.08 251.813 / 18%)', // blue-300/18%
  vacationEasterStroke: 'oklch(0.72 0.08 293.571 / 18%)', // violet-300/18%
  vacationDefaultStroke: 'oklch(0.65 0.01 286.067 / 18%)', // zinc-400/18%
  vacationSolidBg: 'oklch(0.21 0.006 285.885)', // zinc-900 - solid background to cover weekends

  // Row backgrounds - very subtle to not compete with weekend bands
  zebraEven: 'oklch(0.985 0 0 / 1%)', // white/1%
  zebraOdd: 'transparent',
  tenantRow: 'oklch(0.274 0.006 286.033 / 60%)', // zinc-800/60%

  // Grid and axis
  gridLine: 'oklch(0.65 0.01 286.067 / 6%)', // zinc-400 dimmed/6%
  sundayMark: 'oklch(0.65 0.01 286.067 / 4%)', // zinc-400/4% - subtle Sunday markers
  yearMarker: 'oklch(0.985 0 0 / 10%)', // white/10%
  axisText: 'oklch(0.705 0.015 286.067)', // zinc-400

  // Tooltips
  tooltipBg: 'oklch(0.21 0.006 285.885 / 95%)', // zinc-900/95%
  tooltipText: 'oklch(0.929 0.013 255.508)', // slate-200
  tooltipBorder: 'oklch(0.985 0 0 / 10%)', // white/10%

  // Center line indicator
  centerLine: 'oklch(0.712 0.194 13.428 / 50%)', // rose-400/50%
  centerDateBg: 'oklch(0.274 0.006 286.033 / 90%)', // zinc-800/90%
  centerDateText: 'oklch(0.929 0.013 255.508)', // slate-200
  centerDateBorder: 'oklch(0.985 0 0 / 15%)', // white/15%

  // Month headers
  monthHeaderBg: 'oklch(0.274 0.006 286.033 / 80%)', // zinc-800/80%
  monthHeaderText: 'oklch(0.705 0.015 286.067)', // zinc-400

  // Axis background (solid, for sticky header)
  axisBg: 'oklch(0.21 0.006 285.885)', // zinc-900
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
