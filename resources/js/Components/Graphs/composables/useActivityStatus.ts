import { trans } from 'laravel-vue-i18n';

import type { RepresentativeActivityCategory } from '../types';

import { statusRoleParts, type StatusRole } from '@/Constants/statuses';

/** The five backend categories fold into four levels ('today' and 'week' are both active). */
export type ActivityLevel = 'active' | 'recent' | 'stale' | 'never';

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

const LEVEL_ROLES: Record<ActivityLevel, StatusRole> = {
  active: 'success',
  recent: 'attention',
  stale: 'neutral',
  never: 'danger',
};

/** Hex fallbacks for when the theme tokens cannot be read (tests, server rendering). */
const FALLBACK_RING: Record<StatusRole, [light: string, dark: string]> = {
  neutral: ['#52525b', '#71717a'],
  info: ['#2563eb', '#60a5fa'],
  progress: ['#7c3aed', '#a78bfa'],
  attention: ['#d97706', '#f59e0b'],
  success: ['#059669', '#10b981'],
  danger: ['#dc2626', '#ef4444'],
};

export function getActivityRole(category: RepresentativeActivityCategory | undefined): StatusRole {
  const level = getActivityLevel(category);
  return level ? LEVEL_ROLES[level] : 'neutral';
}

/**
 * Ring colour for the D3 Gantt, which sets SVG attributes and so needs a concrete colour
 * rather than a class: the resolved `--status-*` token of the current theme.
 */
export function getActivityRingColor(category: RepresentativeActivityCategory | undefined, isDark: boolean): string | null {
  if (!getActivityLevel(category)) return null;

  const role = getActivityRole(category);
  const token = typeof document === 'undefined'
    ? ''
    : getComputedStyle(document.documentElement).getPropertyValue(`--status-${role}`).trim();

  return token || FALLBACK_RING[role][isDark ? 1 : 0];
}

export function getActivityDotClasses(category: RepresentativeActivityCategory | undefined): string {
  return statusRoleParts[getActivityRole(category)].dot;
}

export function getActivityTextClasses(category: RepresentativeActivityCategory | undefined): string {
  return statusRoleParts[getActivityRole(category)].text;
}

export function getActivityLabel(category: RepresentativeActivityCategory | undefined): string {
  if (!category) return '';
  return trans(`activity.status.${category}`);
}

export function isDutyCurrentlyActive(startDate: Date, endDate: Date | null): boolean {
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const start = new Date(startDate);
  start.setHours(0, 0, 0, 0);

  if (start > today) return false;

  if (!endDate) return true;

  const end = new Date(endDate);
  end.setHours(0, 0, 0, 0);

  return end >= today;
}
