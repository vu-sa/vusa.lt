import { describe, expect, it } from 'vitest';

import {
  getNotificationColorClasses,
  getNotificationColorKey,
  getNotificationContext,
  getNotificationCategoryTag,
  getNotificationPrimaryAction,
  getNotificationSecondaryAction,
  type Notification,
  type NotificationData,
} from '@/Composables/useNotificationFormatting';

const make = (data: NotificationData): Notification => ({
  id: '1',
  type: 'App\\Notifications\\TaskAssignedNotification',
  data,
  created_at: '2026-09-20T10:00:00Z',
  read_at: null,
});

describe('notification contract readers', () => {
  it('reads the primary and secondary action from the contract', () => {
    const notification = make({
      primaryAction: { label: 'Registruoti posėdį', url: '/a' },
      secondaryAction: { label: 'Pranešti apie veiklą', url: '/b' },
    });

    expect(getNotificationPrimaryAction(notification)).toEqual({ label: 'Registruoti posėdį', url: '/a' });
    expect(getNotificationSecondaryAction(notification)).toEqual({ label: 'Pranešti apie veiklą', url: '/b' });
  });

  it('falls back to the legacy actions list for rows stored before the contract', () => {
    const notification = make({
      actions: [{ label: 'Peržiūrėti', url: '/old' }, { label: 'Antras', url: '/old-2' }],
    });

    expect(getNotificationPrimaryAction(notification)?.url).toBe('/old');
    expect(getNotificationSecondaryAction(notification)?.url).toBe('/old-2');
  });

  it('prefers the contract over the legacy list and treats a null action as absent', () => {
    const notification = make({
      primaryAction: { label: 'Nauja', url: '/new' },
      secondaryAction: null,
      actions: [{ label: 'Sena', url: '/old' }],
    });

    expect(getNotificationPrimaryAction(notification)?.url).toBe('/new');
    expect(getNotificationSecondaryAction(notification)).toBeNull();
  });

  it('returns null when a notification only reports something', () => {
    const notification = make({ title: 'Sveiki' });

    expect(getNotificationPrimaryAction(notification)).toBeNull();
    expect(getNotificationSecondaryAction(notification)).toBeNull();
  });

  it('defaults context to no rows and caps it at four', () => {
    expect(getNotificationContext(make({}))).toEqual([]);

    const rows = Array.from({ length: 6 }, (_, i) => ({ label: `L${i}`, value: `V${i}` }));

    expect(getNotificationContext(make({ context: rows }))).toHaveLength(4);
  });
});

describe('notification category colour', () => {
  it('uses the categorical token stored on new rows', () => {
    const notification = make({ category: 'task', color: 'cat-6' });

    expect(getNotificationColorKey(notification)).toBe('cat-6');
    expect(getNotificationColorClasses(notification).combined).toBe('bg-cat-6-surface text-cat-6');
  });

  it('maps a hue name stored before the remap onto its token', () => {
    expect(getNotificationColorKey(make({ category: 'comment', color: 'blue' }))).toBe('cat-2');
    expect(getNotificationColorKey(make({ category: 'meeting', color: 'green' }))).toBe('cat-8');
  });

  it('shows the old System red as neutral, so a category never reads as danger', () => {
    expect(getNotificationColorKey(make({ category: 'system', color: 'red' }))).toBe('neutral');
  });

  it('falls back to the category, then to neutral', () => {
    expect(getNotificationColorKey(make({ category: 'duty' }))).toBe('cat-7');
    expect(getNotificationColorKey({ ...make({}), type: 'App\\Notifications\\SomethingElse' })).toBe('neutral');
  });
});

describe('notification category tag', () => {
  it('extracts human-readable category tag from category, modelKey or type', () => {
    expect(getNotificationCategoryTag(make({ category: 'task' }))).toBe('Užduotis');
    expect(getNotificationCategoryTag(make({ category: 'meeting' }))).toBe('Posėdis');
    expect(getNotificationCategoryTag(make({ category: 'reservation' }))).toBe('Rezervacija');
    expect(getNotificationCategoryTag(make({ category: 'comment' }))).toBe('Komentaras');
    expect(getNotificationCategoryTag(make({ modelClass: 'Duty' }))).toBe('Pareigybė');
    expect(getNotificationCategoryTag({ ...make({}), type: 'App\\Notifications\\StudentRepRegistrationNotification' })).toBe('Registracija');
    expect(getNotificationCategoryTag({ ...make({}), type: 'App\\Notifications\\UnknownNotification' })).toBe('Pranešimas');
  });
});

