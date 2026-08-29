/**
 * The timeline's own palette.
 *
 * The meetings chart's tokens are reused for the grid and the today line, but its *bar*
 * colours encode meeting alignment, not assignment status — borrowing them left an active
 * seat and one that ended in 2019 drawn identically, and spent the same amber on both an
 * ex-officio row and a projected one.
 */
export interface TimelineColors {
  /** Currently held: no end date, or one that has not passed. */
  active: string;
  /** Already ended — present for history, never the thing you are editing. */
  former: string;
  /** Ex-officio: mirrored from a source row, drawn dashed. */
  derived: string;
  /** Unsaved, moved by the user. */
  staged: string;
  /** Unsaved, moved *by* a staged source rather than by the user. */
  projected: string;
  /** Cross-tenant representative — an outline, so status stays readable underneath. */
  crossTenantStroke: string;
  /** Cadence bands alternate between these, the way table rows alternate. */
  cadenceBand: [string, string];
  cadenceBandStroke: string;
  /** A term the cadence filter selected. Loud enough to read as the subject of the view. */
  cadenceBandHighlight: string;
  cadenceBandHighlightStroke: string;
  /** Everything the filter left out, pushed back so the selection reads against it. */
  cadenceBandDim: string;
  /** Month zebra. The meetings palette's `zebraOdd` is transparent in both themes. */
  monthBand: string;
  severity: { error: string; warning: string; info: string };
}

const LIGHT: TimelineColors = {
  active: 'oklch(0.546 0.215 262.881)', // blue-600
  former: 'oklch(0.705 0.015 286.067 / 55%)', // zinc-400/55%
  derived: 'oklch(0.666 0.179 58.318)', // amber-600
  staged: 'oklch(0.596 0.145 163.225)', // emerald-600
  projected: 'oklch(0.596 0.145 163.225 / 45%)', // emerald-600/45%
  crossTenantStroke: 'oklch(0.541 0.281 293.009)', // violet-600
  cadenceBand: ['oklch(0.696 0.17 162.48 / 10%)', 'oklch(0.696 0.17 162.48 / 4%)'],
  cadenceBandStroke: 'oklch(0.696 0.17 162.48 / 35%)',
  cadenceBandHighlight: 'oklch(0.696 0.17 162.48 / 26%)',
  cadenceBandHighlightStroke: 'oklch(0.596 0.145 163.225 / 80%)',
  cadenceBandDim: 'oklch(0.552 0.016 285.938 / 3%)',
  monthBand: 'oklch(0.552 0.016 285.938 / 5%)',
  severity: {
    error: 'oklch(0.577 0.245 27.325)', // red-600
    warning: 'oklch(0.666 0.179 58.318)', // amber-600
    info: 'oklch(0.552 0.016 285.938)', // zinc-500
  },
};

const DARK: TimelineColors = {
  active: 'oklch(0.707 0.165 254.624)', // blue-400
  former: 'oklch(0.552 0.016 285.938 / 60%)', // zinc-500/60%
  derived: 'oklch(0.828 0.189 84.429)', // amber-400
  staged: 'oklch(0.765 0.177 163.223)', // emerald-400
  projected: 'oklch(0.765 0.177 163.223 / 40%)', // emerald-400/40%
  crossTenantStroke: 'oklch(0.75 0.18 293.009)', // violet-400
  cadenceBand: ['oklch(0.65 0.12 163.223 / 9%)', 'oklch(0.65 0.12 163.223 / 3%)'],
  cadenceBandStroke: 'oklch(0.65 0.12 163.223 / 25%)',
  cadenceBandHighlight: 'oklch(0.65 0.12 163.223 / 24%)',
  cadenceBandHighlightStroke: 'oklch(0.765 0.177 163.223 / 75%)',
  cadenceBandDim: 'oklch(0.985 0 0 / 2%)',
  monthBand: 'oklch(0.985 0 0 / 4%)',
  severity: {
    error: 'oklch(0.704 0.191 22.216)', // red-400
    warning: 'oklch(0.828 0.189 84.429)', // amber-400
    info: 'oklch(0.705 0.015 286.067)', // zinc-400
  },
};

export function getTimelineColors(isDark: boolean): TimelineColors {
  return isDark ? DARK : LIGHT;
}

/** Open-ended, or ending today or later. Matches Dutiable::scopeCurrent() on the server. */
export function isActivePeriod(endDate: Date | null, today = new Date()): boolean {
  if (endDate === null) return true;

  const cutoff = new Date(today.getFullYear(), today.getMonth(), today.getDate());

  return endDate >= cutoff;
}
