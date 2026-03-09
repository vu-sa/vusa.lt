/**
 * useActivityStatus - Shared activity status styling and utilities
 * 
 * Provides consistent color mappings and labels for user activity status
 * across all components: Gantt chart avatars, RepresentativeUserRow, etc.
 * 
 * Color scheme:
 * - Active (today or last 7 days) → Green (emerald)
 * - Recent (last 30 days) → Amber
 * - Stale (logged in previously, >30 days ago) → Gray (zinc)
 * - Never (never logged in) → Red
 */

import type { RepresentativeActivityCategory } from '../types';

/**
 * Simplified activity level for styling purposes
 * Maps the 5 backend categories into 4 visual levels
 */
export type ActivityLevel = 'active' | 'recent' | 'stale' | 'never';

/**
 * Convert backend category to simplified activity level
 * - 'today' and 'week' both map to 'active' (green)
 * - 'month' maps to 'recent' (amber)
 * - 'stale' stays 'stale' (gray)
 * - 'never' stays 'never' (red)
 */
export function getActivityLevel(category: RepresentativeActivityCategory | undefined): ActivityLevel | null {
  if (!category) return null;
  
  switch (category) {
    case 'today':
    case 'week':
      return 'active';
    case 'month':
      return 'recent';
    case 'stale':
      return 'stale';
    case 'never':
      return 'never';
    default:
      return null;
  }
}

/**
 * Get ring/stroke color for activity status (used in D3 Gantt chart)
 * Returns hex color string or null if no status
 */
export function getActivityRingColor(category: RepresentativeActivityCategory | undefined, isDark: boolean): string | null {
  const level = getActivityLevel(category);
  if (!level) return null;
  
  switch (level) {
    case 'active':
      return isDark ? '#10b981' : '#059669'; // emerald-500/600
    case 'recent':
      return isDark ? '#f59e0b' : '#d97706'; // amber-500/600
    case 'stale':
      return isDark ? '#71717a' : '#52525b'; // zinc-500/600
    case 'never':
      return isDark ? '#ef4444' : '#dc2626'; // red-500/600
    default:
      return null;
  }
}

/**
 * Get Tailwind CSS classes for activity status dot
 * Used in RepresentativeUserRow and similar components
 */
export function getActivityDotClasses(category: RepresentativeActivityCategory | undefined): string {
  const level = getActivityLevel(category);
  
  switch (level) {
    case 'active':
      return 'bg-emerald-500 dark:bg-emerald-400';
    case 'recent':
      return 'bg-amber-500 dark:bg-amber-400';
    case 'stale':
      return 'bg-zinc-400 dark:bg-zinc-500';
    case 'never':
      return 'bg-red-500 dark:bg-red-400';
    default:
      return 'bg-zinc-400 dark:bg-zinc-500';
  }
}

/**
 * Get Tailwind CSS classes for activity status text
 */
export function getActivityTextClasses(category: RepresentativeActivityCategory | undefined): string {
  const level = getActivityLevel(category);
  
  switch (level) {
    case 'active':
      return 'text-emerald-600 dark:text-emerald-400';
    case 'recent':
      return 'text-amber-600 dark:text-amber-400';
    case 'stale':
      return 'text-zinc-500 dark:text-zinc-400';
    case 'never':
      return 'text-red-600 dark:text-red-400';
    default:
      return 'text-zinc-500 dark:text-zinc-400';
  }
}

/**
 * Get Tailwind CSS classes for activity status badge (background + text)
 * Used in RepresentativeDataTable and similar components
 */
export function getActivityBadgeClasses(category: RepresentativeActivityCategory | undefined): string {
  const level = getActivityLevel(category);
  
  switch (level) {
    case 'active':
      return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
    case 'recent':
      return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    case 'stale':
      return 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400';
    case 'never':
      return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
    default:
      return 'bg-zinc-200 text-zinc-600';
  }
}

/**
 * Get short label for activity status (used in badges)
 */
export function getActivityShortLabel(category: RepresentativeActivityCategory | undefined): string {
  if (!category) return '';
  
  const labels: Record<RepresentativeActivityCategory, string> = {
    'today': 'Šiandien',
    'week': 'Per 7 d.',
    'month': 'Per 30 d.',
    'stale': 'Seniai',
    'never': 'Niekada',
  };
  
  return labels[category] ?? '';
}

/**
 * Get localized label for activity status (used in tooltips)
 */
export function getActivityLabel(category: RepresentativeActivityCategory | undefined): string {
  if (!category) return '';
  
  // These are Lithuanian strings that will be translated via laravel-vue-i18n
  // when used in Vue components. For D3 renderers, we use these directly.
  const labels: Record<RepresentativeActivityCategory, string> = {
    'today': 'Aktyvus šiandien',
    'week': 'Aktyvus per 7 dienas',
    'month': 'Aktyvus per 30 dienų',
    'stale': 'Neaktyvus > 30 d.',
    'never': 'Niekada neprisijungęs',
  };
  
  return labels[category] ?? '';
}

/**
 * Get localized tooltip label (longer form for tooltips)
 */
export function getActivityTooltipLabel(category: RepresentativeActivityCategory | undefined): string {
  if (!category) return '';
  
  const labels: Record<RepresentativeActivityCategory, string> = {
    'today': 'Prisijungė šiandien',
    'week': 'Prisijungė per pastarąsias 7 dienas',
    'month': 'Prisijungė per pastarąsias 30 dienų',
    'stale': 'Neprisijungė daugiau nei 30 dienų',
    'never': 'Niekada neprisijungė prie sistemos',
  };
  
  return labels[category] ?? '';
}

/**
 * Check if a duty member is currently active (for filtering Gantt chart rings)
 * A member is current if their duty period includes today:
 * - endDate is null (ongoing) OR endDate >= today
 * - AND startDate <= today
 */
export function isDutyCurrentlyActive(startDate: Date, endDate: Date | null): boolean {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  const start = new Date(startDate);
  start.setHours(0, 0, 0, 0);
  
  // Start date must be in the past or today
  if (start > today) return false;
  
  // If no end date, duty is ongoing
  if (!endDate) return true;
  
  const end = new Date(endDate);
  end.setHours(0, 0, 0, 0);
  
  // End date must be today or in the future
  return end >= today;
}
