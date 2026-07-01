/**
 * Colored badge helpers for search status / vote / favorability values.
 * Tones map to Tailwind utility classes (aligned with the app status palette).
 */

export type BadgeTone = 'success' | 'danger' | 'warning' | 'info' | 'neutral' | 'related';

// Each tone pins its own hover:* to the base color so the Badge `default`
// variant's built-in hover (hover:bg-zinc-900/80 / dark:hover:bg-zinc-50/80)
// can't bleed through and flip the badge dark/bright on hover.
const TONE_CLASSES: Record<BadgeTone, string> = {
  success: 'border-transparent bg-emerald-100 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-400 dark:hover:bg-emerald-950/50',
  danger: 'border-transparent bg-red-100 text-red-700 hover:bg-red-100 dark:bg-red-950/50 dark:text-red-400 dark:hover:bg-red-950/50',
  warning: 'border-transparent bg-amber-100 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/50 dark:text-amber-400 dark:hover:bg-amber-950/50',
  info: 'border-transparent bg-blue-100 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/50 dark:text-blue-400 dark:hover:bg-blue-950/50',
  neutral: 'border-transparent bg-muted text-muted-foreground hover:bg-muted dark:hover:bg-muted',
  related: 'border-transparent bg-purple-100 text-purple-700 hover:bg-purple-100 dark:bg-purple-950/50 dark:text-purple-400 dark:hover:bg-purple-950/50',
};

export function toneClass(tone: BadgeTone): string {
  return TONE_CLASSES[tone];
}

/** VoteValue (positive/negative/neutral) → tone, for vote/decision/favorability. */
export function voteTone(value?: string | null): BadgeTone {
  switch (value) {
    case 'positive': return 'success';
    case 'negative': return 'danger';
    default: return 'neutral';
  }
}

/** Meeting completion_status → tone. */
export function completionTone(value?: string | null): BadgeTone {
  switch (value) {
    case 'complete': return 'success';
    case 'incomplete':
    case 'partial': return 'warning';
    default: return 'neutral';
  }
}

/** vote_alignment_status → tone. */
export function alignmentTone(value?: string | null): BadgeTone {
  switch (value) {
    case 'aligned':
    case 'all_match':
    case 'match': return 'success';
    case 'misaligned':
    case 'all_mismatch':
    case 'mismatch': return 'danger';
    case 'mixed': return 'warning';
    default: return 'neutral';
  }
}
