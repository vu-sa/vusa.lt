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
  actions?: Array<{ label: string; url: string }>;
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

// Color palette for notification categories
export const notificationColors = {
  blue: {
    bg: 'bg-blue-100 dark:bg-blue-900/30',
    text: 'text-blue-600 dark:text-blue-400',
    combined: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    border: 'border-blue-200 dark:border-blue-800',
  },
  orange: {
    bg: 'bg-orange-100 dark:bg-orange-900/30',
    text: 'text-orange-600 dark:text-orange-400',
    combined: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
    border: 'border-orange-200 dark:border-orange-800',
  },
  purple: {
    bg: 'bg-purple-100 dark:bg-purple-900/30',
    text: 'text-purple-600 dark:text-purple-400',
    combined: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
    border: 'border-purple-200 dark:border-purple-800',
  },
  green: {
    bg: 'bg-green-100 dark:bg-green-900/30',
    text: 'text-green-600 dark:text-green-400',
    combined: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    border: 'border-green-200 dark:border-green-800',
  },
  cyan: {
    bg: 'bg-cyan-100 dark:bg-cyan-900/30',
    text: 'text-cyan-600 dark:text-cyan-400',
    combined: 'bg-cyan-100 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400',
    border: 'border-cyan-200 dark:border-cyan-800',
  },
  gray: {
    bg: 'bg-zinc-100 dark:bg-zinc-800/50',
    text: 'text-zinc-600 dark:text-zinc-400',
    combined: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800/50 dark:text-zinc-400',
    border: 'border-zinc-200 dark:border-zinc-700',
  },
  amber: {
    bg: 'bg-amber-100 dark:bg-amber-900/30',
    text: 'text-amber-600 dark:text-amber-400',
    combined: 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
    border: 'border-amber-200 dark:border-amber-800',
  },
  red: {
    bg: 'bg-red-100 dark:bg-red-900/30',
    text: 'text-red-600 dark:text-red-400',
    combined: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    border: 'border-red-200 dark:border-red-800',
  },
} as const;

export type NotificationColorKey = keyof typeof notificationColors;

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

  // New standardized structure uses color
  if (data.color && data.color in notificationColors) {
    return data.color as NotificationColorKey;
  }

  // Map category to color
  const { category } = data;
  if (category) {
    const categoryColorMap: Record<string, NotificationColorKey> = {
      comment: 'blue',
      task: 'orange',
      reservation: 'purple',
      meeting: 'green',
      registration: 'green',
      user: 'cyan',
      duty: 'amber',
      system: 'gray',
    };
    return categoryColorMap[category] || 'gray';
  }

  // Legacy type-based styling
  const type = getNotificationType(notification);
  return getColorByType(type);
}

/**
 * Get color by notification type (legacy support)
 */
function getColorByType(type: string): NotificationColorKey {
  switch (type) {
    case 'ModelCommented':
    case 'CommentPostedNotification':
      return 'blue';
    case 'MemberRegistered':
    case 'MemberRegistrationNotification':
    case 'StudentRepRegistrationNotification':
      return 'green';
    case 'UserAttachedToModel':
    case 'AssignedToResourceNotification':
      return 'purple';
    case 'TaskAssignedNotification':
    case 'TaskCompletedNotification':
    case 'TaskCreatedNotification':
      return 'orange';
    case 'TaskOverdueNotification':
      return 'red';
    case 'ReservationStatusChangedNotification':
      return 'purple';
    case 'MeetingReminderNotification':
      return 'green';
    case 'DutyExpiringNotification':
      return 'amber';
    case 'WelcomeNotification':
      return 'cyan';
    default:
      return 'gray';
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
 * Check if notification can be muted (has associated object)
 */
export function canMuteNotification(notification: Notification): boolean {
  const obj = notification.data.object;
  return !!(obj?.modelClass && obj?.id);
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
