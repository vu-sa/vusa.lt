/**
 * The timeline's own palette, mirroring the admin tokens (`theme/surface-palette.css`,
 * `theme/base-tokens.css`): d3 needs literal values, so keep the two in step.
 *
 * Ink is the data and amber (`--status-attention`) is what still needs you — an unsaved
 * move. Brand red is never a bar: it is the page's one primary action, and a chart full of
 * it read as forty buttons.
 */
export interface TimelineColors {
  /** Currently held: no end date, or one that has not passed. */
  active: string;
  /** Already ended — present for history, never the thing you are editing. */
  former: string;
  /** Ex-officio: mirrored from a source row, a pale fill under a dashed outline. */
  derived: string;
  derivedStroke: string;
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
  active: 'oklch(0.17 0.008 70 / 85%)', // --foreground
  former: 'oklch(0.46 0.01 75 / 40%)', // --muted-foreground
  derived: 'oklch(0.17 0.008 70 / 22%)',
  derivedStroke: 'oklch(0.17 0.008 70 / 85%)',
  staged: 'oklch(0.55 0.16 55)', // --status-attention
  projected: 'oklch(0.55 0.16 55 / 45%)',
  crossTenantStroke: 'oklch(0.52 0.11 300)', // --cat-4
  cadenceBand: ['oklch(0.17 0.008 70 / 6%)', 'oklch(0.17 0.008 70 / 2.5%)'],
  cadenceBandStroke: 'oklch(0.17 0.008 70 / 18%)',
  cadenceBandHighlight: 'oklch(0.17 0.008 70 / 12%)',
  cadenceBandHighlightStroke: 'oklch(0.17 0.008 70 / 70%)',
  cadenceBandDim: 'oklch(0.17 0.008 70 / 1.5%)',
  monthBand: 'oklch(0.17 0.008 70 / 3%)',
  severity: {
    error: 'oklch(0.52 0.19 25)', // --status-danger
    warning: 'oklch(0.55 0.16 55)', // --status-attention
    info: 'oklch(0.45 0.01 80)', // --status-neutral
  },
};

const DARK: TimelineColors = {
  active: 'oklch(0.955 0.004 85 / 85%)', // --foreground
  former: 'oklch(0.68 0.008 78 / 40%)', // --muted-foreground
  derived: 'oklch(0.955 0.004 85 / 20%)',
  derivedStroke: 'oklch(0.955 0.004 85 / 80%)',
  staged: 'oklch(0.8 0.13 60)', // --status-attention
  projected: 'oklch(0.8 0.13 60 / 40%)',
  crossTenantStroke: 'oklch(0.76 0.11 300)', // --cat-4
  cadenceBand: ['oklch(0.955 0.004 85 / 6%)', 'oklch(0.955 0.004 85 / 2.5%)'],
  cadenceBandStroke: 'oklch(0.955 0.004 85 / 16%)',
  cadenceBandHighlight: 'oklch(0.955 0.004 85 / 12%)',
  cadenceBandHighlightStroke: 'oklch(0.955 0.004 85 / 65%)',
  cadenceBandDim: 'oklch(0.955 0.004 85 / 1.5%)',
  monthBand: 'oklch(0.955 0.004 85 / 3%)',
  severity: {
    error: 'oklch(0.75 0.16 25)', // --status-danger
    warning: 'oklch(0.8 0.13 60)', // --status-attention
    info: 'oklch(0.72 0.01 80)', // --status-neutral
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
