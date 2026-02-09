/**
 * Shared styling utilities for dashboard cards
 *
 * This composable provides consistent styling classes for dashboard cards
 * including gradients, borders, and urgency-based color schemes.
 */

import { computed, type ComputedRef } from 'vue';

export type UrgencyLevel = 'success' | 'warning' | 'danger' | 'neutral';

/**
 * Base card classes for dashboard cards with gradient background
 */
export const dashboardCardClasses = [
  'flex flex-col relative overflow-hidden shadow-sm dark:shadow-zinc-950/50',
  'border-zinc-200 dark:border-zinc-800',
  'bg-gradient-to-br from-white to-zinc-50 dark:from-zinc-900 dark:to-zinc-950',
].join(' ');

/**
 * Card footer classes
 */
export const dashboardCardFooterClasses
  = 'border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/60';

/**
 * Get urgency-based status indicator classes (corner accent)
 */
export function getStatusIndicatorClasses(urgency: UrgencyLevel): string {
  const base = 'absolute top-0 right-0 w-12 h-12 -mr-6 -mt-6 rotate-45';
  const urgencyClasses: Record<UrgencyLevel, string> = {
    success: 'bg-emerald-400/60 dark:bg-emerald-700/35',
    warning: 'bg-amber-400/60 dark:bg-amber-700/35',
    danger: 'bg-zinc-200 dark:bg-zinc-700',
    neutral: 'bg-zinc-200 dark:bg-zinc-700',
  };
  return `${base} ${urgencyClasses[urgency]}`;
}

/**
 * Get urgency-based icon classes
 */
export function getIconClasses(urgency: UrgencyLevel): string {
  const urgencyClasses: Record<UrgencyLevel, string> = {
    success: 'h-5 w-5 text-emerald-600 dark:text-emerald-400/80',
    warning: 'h-5 w-5 text-amber-600 dark:text-amber-400/80',
    danger: 'h-5 w-5 text-zinc-600 dark:text-zinc-400',
    neutral: 'h-5 w-5 text-zinc-600 dark:text-zinc-400',
  };
  return urgencyClasses[urgency];
}

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

/**
 * Composable for dashboard card urgency-based styling
 *
 * @param urgencyLevel - Computed or ref containing the urgency level
 * @returns Object with computed classes for various card elements
 *
 * @example
 * ```ts
 * const urgency = computed(() => needsAttention ? 'warning' : 'success')
 * const { statusIndicatorClasses, iconClasses, badgeClasses } = useDashboardCardStyles(urgency)
 * ```
 */
export function useDashboardCardStyles(urgencyLevel: ComputedRef<UrgencyLevel>) {
  const statusIndicatorClasses = computed(() => {
    const base = 'absolute top-0 right-0 w-12 h-12 -mr-6 -mt-6 rotate-45';
    return `${base} ${urgencyPalette.statusIndicator[urgencyLevel.value]}`;
  });

  const iconClasses = computed(() => {
    return `h-5 w-5 ${urgencyPalette.icon[urgencyLevel.value]}`;
  });

  const borderClasses = computed(() => urgencyPalette.border[urgencyLevel.value]);
  const textClasses = computed(() => urgencyPalette.text[urgencyLevel.value]);
  const subtextClasses = computed(() => urgencyPalette.subtext[urgencyLevel.value]);
  const backgroundClasses = computed(() => urgencyPalette.background[urgencyLevel.value]);
  const foregroundClasses = computed(() => urgencyPalette.foreground[urgencyLevel.value]);
  const badgeClasses = computed(() => urgencyPalette.badge[urgencyLevel.value]);

  return {
    // Base card class (static)
    cardClasses: dashboardCardClasses,
    footerClasses: dashboardCardFooterClasses,
    // Dynamic classes based on urgency
    statusIndicatorClasses,
    iconClasses,
    borderClasses,
    textClasses,
    subtextClasses,
    backgroundClasses,
    foregroundClasses,
    badgeClasses,
  };
}

/**
 * Specific accent color palettes for different card types
 */
export const cardAccentColors = {
  // Amber - for tasks, meetings (attention/action items)
  amber: {
    statusIndicator: 'bg-amber-400/50 dark:bg-amber-600/25',
    statusIndicatorActive: 'bg-amber-400/60 dark:bg-amber-700/35',
    icon: 'text-amber-600 dark:text-amber-400/80',
    iconMuted: 'text-zinc-600 dark:text-zinc-400',
    badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400/80',
    text: 'text-amber-600 dark:text-amber-400',
    dot: 'bg-amber-500 dark:bg-amber-400',
  },
  // Red - for calendar events (vusa-red brand color)
  red: {
    statusIndicator: 'bg-vusa-red/30 dark:bg-vusa-red/20',
    icon: 'text-vusa-red dark:text-vusa-red',
    badge: 'bg-vusa-red/10 dark:bg-vusa-red/20',
    text: 'text-vusa-red dark:text-vusa-red',
  },
  // Blue - for news
  blue: {
    statusIndicator: 'bg-blue-400/30 dark:bg-blue-500/20',
    icon: 'text-blue-600 dark:text-blue-400',
    badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    text: 'text-blue-600 dark:text-blue-400',
  },
  // Emerald - for success states
  emerald: {
    statusIndicator: 'bg-emerald-400/60 dark:bg-emerald-700/35',
    icon: 'text-emerald-600 dark:text-emerald-400/80',
    badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400/80',
    text: 'text-emerald-700 dark:text-emerald-400/80',
  },
} as const;
