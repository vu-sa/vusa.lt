/**
 * Notification Formatting Composable
 *
 * Provides unified formatting utilities for notification display
 * across toasts, dropdown indicator, and full page views.
 * Handles both new standardized structure and legacy notification formats.
 */

import { trans as $t } from 'laravel-vue-i18n';
import type { Component } from 'vue';

import { formatRelativeTime } from '@/Utils/IntlTime';
import { getModelIcon } from '@/Components/icons';
import type { ModelEnum } from '@/Types/enums';

// Default icons for notification categories
import IFluentComment24Regular from '~icons/fluent/comment24-regular';
import IFluentTaskListSquareLtr24Regular from '~icons/fluent/task-list-square-ltr24-regular';
import IFluentBookmark24Regular from '~icons/fluent/bookmark24-regular';
import IFluentDeviceMeetingRoomRemote24Regular from '~icons/fluent/device-meeting-room-remote24-regular';
import IFluentDocumentBulletList24Regular from '~icons/fluent/document-bullet-list24-regular';
import IFluentPerson24Regular from '~icons/fluent/person24-regular';
import IFluentPuzzlePiece24Regular from '~icons/fluent/puzzle-piece24-regular';
import IFluentAlert24Regular from '~icons/fluent/alert24-regular';

export interface NotificationAction {
  label: string;
  url: string;
}

export interface NotificationContextRow {
  label: string;
  value: string;
}

// Notification data structure (supports both new and legacy formats)
export interface NotificationData {
  // New standardized structure
  category?: string;
  modelClass?: string;
  title?: string;
  body?: string;
  url?: string;
  icon?: string;
  color?: string;
  primaryAction?: NotificationAction | null;
  secondaryAction?: NotificationAction | null;
  context?: NotificationContextRow[];
  /** @deprecated Rows stored before PR 6.1; read primaryAction / secondaryAction instead. */
  actions?: NotificationAction[];
  subject?: {
    modelClass: string;
    name: string;
    image?: string;
  };
  object?: {
    modelClass: string;
    name: string | null;
    url: string;
    id?: string;
  };
  // Legacy fields for backward compatibility
  text?: string;
  message?: string;
}

export interface Notification {
  id: string;
  type: string;
  data: NotificationData;
  created_at: string;
  read_at: string | null;
}

// Category marks: the eight categorical tokens plus neutral — small marks with a label, never status.
export const notificationColors = {
  'cat-1': {
    bg: 'bg-cat-1-surface',
    text: 'text-cat-1',
    combined: 'bg-cat-1-surface text-cat-1',
    border: 'border-cat-1/30',
  },
  'cat-2': {
    bg: 'bg-cat-2-surface',
    text: 'text-cat-2',
    combined: 'bg-cat-2-surface text-cat-2',
    border: 'border-cat-2/30',
  },
  'cat-3': {
    bg: 'bg-cat-3-surface',
    text: 'text-cat-3',
    combined: 'bg-cat-3-surface text-cat-3',
    border: 'border-cat-3/30',
  },
  'cat-4': {
    bg: 'bg-cat-4-surface',
    text: 'text-cat-4',
    combined: 'bg-cat-4-surface text-cat-4',
    border: 'border-cat-4/30',
  },
  'cat-5': {
    bg: 'bg-cat-5-surface',
    text: 'text-cat-5',
    combined: 'bg-cat-5-surface text-cat-5',
    border: 'border-cat-5/30',
  },
  'cat-6': {
    bg: 'bg-cat-6-surface',
    text: 'text-cat-6',
    combined: 'bg-cat-6-surface text-cat-6',
    border: 'border-cat-6/30',
  },
  'cat-7': {
    bg: 'bg-cat-7-surface',
    text: 'text-cat-7',
    combined: 'bg-cat-7-surface text-cat-7',
    border: 'border-cat-7/30',
  },
  'cat-8': {
    bg: 'bg-cat-8-surface',
    text: 'text-cat-8',
    combined: 'bg-cat-8-surface text-cat-8',
    border: 'border-cat-8/30',
  },
  'neutral': {
    bg: 'bg-status-neutral-surface',
    text: 'text-status-neutral',
    combined: 'bg-status-neutral-surface text-status-neutral',
    border: 'border-status-neutral-border',
  },
} as const;

export type NotificationColorKey = keyof typeof notificationColors;

/**
 * @deprecated Rows stored before PR 6.5 carry a hue name in `data.color`; drop this map (and the
 * fallback in getNotificationColorKey) once those rows have aged out — Phase 10.
 */
const legacyHueToToken: Record<string, NotificationColorKey> = {
  blue: 'cat-2',
  orange: 'cat-6',
  purple: 'cat-4',
  green: 'cat-8',
  cyan: 'cat-1',
  amber: 'cat-7',
  indigo: 'cat-5',
  teal: 'cat-3',
  gray: 'neutral',
  red: 'neutral',
};

/**
 * Extract notification type (class name without namespace)
 */
export function getNotificationType(notification: Notification): string {
  const typeParts = notification.type.split('\\');
  return typeParts[typeParts.length - 1] || 'Unknown';
}

/**
 * Get the model enum key from notification data
 */
export function getModelEnumKey(data: NotificationData): keyof typeof ModelEnum | null {
  const modelClass = data.modelClass || data.object?.modelClass;

  if (!modelClass) return null;

  // Handle direct ModelEnum keys (e.g., 'TASK', 'MEETING')
  if (modelClass === modelClass.toUpperCase()) {
    return modelClass as keyof typeof ModelEnum;
  }

  // Map model class names to ModelEnum keys
  const mapping: Record<string, keyof typeof ModelEnum> = {
    Reservation: 'RESERVATION',
    ReservationResource: 'RESERVATION_RESOURCE',
    Task: 'TASK',
    Meeting: 'MEETING',
    Comment: 'COMMENT',
    Duty: 'DUTY',
    Form: 'FORM',
    User: 'USER',
    Institution: 'INSTITUTION',
  };

  return mapping[modelClass] || null;
}

/**
 * Get notification icon component
 */
export function getNotificationIcon(notification: Notification): Component {
  const { data } = notification;

  // Try to get model-specific icon first
  const modelKey = getModelEnumKey(data);
  if (modelKey) {
    return getModelIcon(modelKey);
  }

  // Fall back to category-based icons
  const { category } = data;
  switch (category) {
    case 'comment':
      return IFluentComment24Regular;
    case 'task':
      return IFluentTaskListSquareLtr24Regular;
    case 'reservation':
      return IFluentBookmark24Regular;
    case 'meeting':
      return IFluentDeviceMeetingRoomRemote24Regular;
    case 'registration':
      return IFluentDocumentBulletList24Regular;
    case 'user':
      return IFluentPerson24Regular;
    case 'duty':
      return IFluentPuzzlePiece24Regular;
    default:
      // Legacy type-based mapping
      return getIconByType(getNotificationType(notification));
  }
}

/**
 * Get icon by notification type (legacy support)
 */
function getIconByType(type: string): Component {
  switch (type) {
    case 'ModelCommented':
    case 'CommentPostedNotification':
      return IFluentComment24Regular;
    case 'MemberRegistered':
    case 'MemberRegistrationNotification':
    case 'StudentRepRegistrationNotification':
      return IFluentDocumentBulletList24Regular;
    case 'UserAttachedToModel':
    case 'AssignedToResourceNotification':
      return IFluentPerson24Regular;
    case 'TaskAssignedNotification':
    case 'TaskCompletedNotification':
    case 'TaskOverdueNotification':
    case 'TaskCreatedNotification':
    case 'TaskReminderNotification':
      return IFluentTaskListSquareLtr24Regular;
    case 'ReservationStatusChangedNotification':
      return IFluentBookmark24Regular;
    case 'MeetingReminderNotification':
      return IFluentDeviceMeetingRoomRemote24Regular;
    case 'DutyExpiringNotification':
      return IFluentPuzzlePiece24Regular;
    case 'WelcomeNotification':
      return IFluentPerson24Regular;
    default:
      return IFluentAlert24Regular;
  }
}

/**
 * Get notification color key
 */
export function getNotificationColorKey(notification: Notification): NotificationColorKey {
  const { data } = notification;

  if (data.color && data.color in notificationColors) {
    return data.color as NotificationColorKey;
  }

  if (data.color && data.color in legacyHueToToken) {
    return legacyHueToToken[data.color];
  }

  // Mirrors NotificationCategory::color()
  const { category } = data;
  if (category) {
    const categoryColorMap: Record<string, NotificationColorKey> = {
      comment: 'cat-2',
      task: 'cat-6',
      reservation: 'cat-4',
      meeting: 'cat-8',
      registration: 'cat-1',
      duty: 'cat-7',
      user: 'neutral',
      system: 'neutral',
    };
    return categoryColorMap[category] || 'neutral';
  }

  return getColorByType(getNotificationType(notification));
}

/**
 * Get color by notification type (legacy support)
 */
function getColorByType(type: string): NotificationColorKey {
  switch (type) {
    case 'ModelCommented':
    case 'CommentPostedNotification':
      return 'cat-2';
    case 'MemberRegistered':
    case 'MemberRegistrationNotification':
    case 'StudentRepRegistrationNotification':
      return 'cat-1';
    case 'UserAttachedToModel':
    case 'AssignedToResourceNotification':
    case 'ReservationStatusChangedNotification':
      return 'cat-4';
    case 'TaskAssignedNotification':
    case 'TaskCompletedNotification':
    case 'TaskCreatedNotification':
    case 'TaskOverdueNotification':
      return 'cat-6';
    case 'MeetingReminderNotification':
      return 'cat-8';
    case 'DutyExpiringNotification':
      return 'cat-7';
    default:
      return 'neutral';
  }
}

// Type for notification color object
export interface NotificationColorStyles {
  bg: string;
  text: string;
  combined: string;
  border: string;
}

/**
 * Get notification color classes
 */
export function getNotificationColorClasses(notification: Notification): NotificationColorStyles {
  const colorKey = getNotificationColorKey(notification);
  return notificationColors[colorKey];
}

/**
 * Get notification title
 */
export function getNotificationTitle(notification: Notification): string {
  const { data } = notification;

  // New standardized structure
  if (data.title) {
    return data.title;
  }

  // Legacy fallbacks based on type
  const type = getNotificationType(notification);
  switch (type) {
    case 'ModelCommented':
    case 'CommentPostedNotification':
      return $t('New Comment');
    case 'MemberRegistered':
    case 'MemberRegistrationNotification':
      return $t('New Member Registration');
    case 'UserAttachedToModel':
    case 'AssignedToResourceNotification':
      return $t('Assignment Notification');
    case 'TaskAssignedNotification':
      return $t('Task Assigned');
    case 'TaskCompletedNotification':
      return $t('Task Completed');
    case 'TaskOverdueNotification':
      return $t('Overdue Tasks');
    case 'TaskCreatedNotification':
      return $t('New Task');
    case 'TaskReminderNotification':
      return $t('Task Reminder');
    case 'ReservationStatusChangedNotification':
      return $t('Reservation Updated');
    case 'MeetingReminderNotification':
      return $t('Meeting Reminder');
    case 'DutyExpiringNotification':
      return $t('Duty Expiring');
    case 'WelcomeNotification':
      return $t('Welcome');
    default:
      return data.subject?.name || $t('Notification');
  }
}

/**
 * Get notification message/body
 */
export function getNotificationMessage(notification: Notification): string {
  const { data } = notification;

  // New standardized structure
  if (data.body) {
    return data.body;
  }

  // Legacy fallback
  if (data.text) {
    return data.text;
  }

  if (data.object?.name) {
    return `${data.subject?.name || ''} ${$t('on')} ${data.object.name}`;
  }

  return data.message || $t('You have a new notification');
}

/**
 * Get notification URL
 */
export function getNotificationUrl(notification: Notification): string | null {
  return notification.data.url || notification.data.object?.url || null;
}

/**
 * The action the notification asks for. Rows stored before PR 6.1 only carry `actions`, so fall
 * back to its first entry.
 */
export function getNotificationPrimaryAction(notification: Notification): NotificationAction | null {
  return notification.data.primaryAction ?? notification.data.actions?.[0] ?? null;
}

/** The second action, present only for binary answers (see getNotificationPrimaryAction for the fallback). */
export function getNotificationSecondaryAction(notification: Notification): NotificationAction | null {
  return notification.data.secondaryAction ?? notification.data.actions?.[1] ?? null;
}

/** Label/value rows saying what the notification is about; capped at four. */
export function getNotificationContext(notification: Notification): NotificationContextRow[] {
  return (notification.data.context ?? []).slice(0, 4);
}

/**
 * Format notification timestamp
 */
export function formatNotificationTime(notification: Notification): string {
  return formatRelativeTime(new Date(notification.created_at));
}

/**
 * Group notifications by time period
 */
export function groupNotificationsByTime(notifications: Notification[]): Map<string, Notification[]> {
  const groups = new Map<string, Notification[]>();
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const yesterday = new Date(today.getTime() - 24 * 60 * 60 * 1000);
  const lastWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);

  for (const notification of notifications) {
    const date = new Date(notification.created_at);
    let period: string;

    if (date >= today) {
      period = $t('Today');
    }
    else if (date >= yesterday) {
      period = $t('Yesterday');
    }
    else if (date >= lastWeek) {
      period = $t('This Week');
    }
    else {
      period = $t('Earlier');
    }

    if (!groups.has(period)) {
      groups.set(period, []);
    }
    groups.get(period)!.push(notification);
  }

  return groups;
}
