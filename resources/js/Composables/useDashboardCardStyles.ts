export type UrgencyLevel = 'success' | 'warning' | 'danger' | 'neutral';

/**
 * Color palette for various card elements based on urgency level
 */
export const urgencyPalette = {
  // Border colors
  border: {
    success: 'border-emerald-200 dark:border-emerald-700/50',
    warning: 'border-amber-200 dark:border-amber-700/50',
    danger: 'border-zinc-200 dark:border-zinc-700',
    neutral: 'border-zinc-200 dark:border-zinc-700',
  },
  // Primary text colors
  text: {
    success: 'text-emerald-700 dark:text-emerald-400/80',
    warning: 'text-amber-700 dark:text-amber-400/80',
    danger: 'text-zinc-700 dark:text-zinc-300',
    neutral: 'text-zinc-700 dark:text-zinc-300',
  },
  // Secondary/subtext colors
  subtext: {
    success: 'text-emerald-600 dark:text-emerald-500/70',
    warning: 'text-amber-600 dark:text-amber-500/70',
    danger: 'text-zinc-600 dark:text-zinc-400',
    neutral: 'text-zinc-600 dark:text-zinc-400',
  },
  // Background colors (for progress bars, badges, etc.)
  background: {
    success: 'bg-emerald-200 dark:bg-emerald-700/50',
    warning: 'bg-amber-200 dark:bg-amber-700/50',
    danger: 'bg-zinc-200 dark:bg-zinc-700',
    neutral: 'bg-zinc-200 dark:bg-zinc-700',
  },
  // Foreground/fill colors (for progress bars, accents)
  foreground: {
    success: 'bg-emerald-600 dark:bg-emerald-500/70',
    warning: 'bg-amber-600 dark:bg-amber-500/70',
    danger: 'bg-zinc-600 dark:bg-zinc-400',
    neutral: 'bg-zinc-600 dark:bg-zinc-400',
  },
  // Badge/pill colors
  badge: {
    success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400/80',
    warning: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400/80',
    danger: 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-300',
    neutral: 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-300',
  },
  // Status indicator (corner accent)
  statusIndicator: {
    success: 'bg-emerald-400/60 dark:bg-emerald-700/35',
    warning: 'bg-amber-400/60 dark:bg-amber-700/35',
    danger: 'bg-zinc-200 dark:bg-zinc-700',
    neutral: 'bg-zinc-200 dark:bg-zinc-700',
  },
  // Icon colors
  icon: {
    success: 'text-emerald-600 dark:text-emerald-400/80',
    warning: 'text-amber-600 dark:text-amber-400/80',
    danger: 'text-zinc-600 dark:text-zinc-400',
    neutral: 'text-zinc-600 dark:text-zinc-400',
  },
} as const;
