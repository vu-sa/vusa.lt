import { describe, test, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import ActivityLogEntry from '../ActivityLogEntry.vue';

import { commonStubs } from '@/tests/stubs';
import type { ActivityEntry, ActivityEvent } from '@/Types/activityLog';

function makeEntry(overrides: Partial<ActivityEntry> = {}): ActivityEntry {
  return {
    id: 1,
    event: 'updated',
    created_at: '2026-01-15T10:00:00Z',
    causer: { id: '01H000000000000000000000', name: 'Jonas Jonaitis', profile_photo_path: null },
    subject: { type: 'meeting', id: '01J000000000000000000000', label: 'Posėdis', is_root: true },
    changes: [],
    ...overrides,
  };
}

describe('ActivityLogEntry', () => {
  test.each<ActivityEvent>(['created', 'updated', 'deleted', 'restored', 'relation_updated'])(
    'renders the %s event label',
    (event) => {
      const wrapper = mount(ActivityLogEntry, {
        props: { entry: makeEntry({ event }) },
        global: { stubs: commonStubs },
      });

      expect(wrapper.text()).toContain(`activity.event.${event}`);
    },
  );

  test('a null causer renders the system fallback instead of UserPopover', () => {
    const wrapper = mount(ActivityLogEntry, {
      props: { entry: makeEntry({ causer: null }) },
      global: { stubs: { ...commonStubs, UserPopover: true } },
    });

    expect(wrapper.text()).toContain('activity.system');
    expect(wrapper.findComponent({ name: 'UserPopover' }).exists()).toBe(false);
  });

  test('a causer renders UserPopover, not the system fallback', () => {
    const wrapper = mount(ActivityLogEntry, {
      props: { entry: makeEntry() },
      global: { stubs: { ...commonStubs, UserPopover: true } },
    });

    expect(wrapper.findComponent({ name: 'UserPopover' }).exists()).toBe(true);
    expect(wrapper.text()).not.toContain('activity.system');
  });

  test('a non-root subject renders the subject badge', () => {
    const wrapper = mount(ActivityLogEntry, {
      props: { entry: makeEntry({ subject: { type: 'vote', id: '1', label: 'Balsavimas', is_root: false } }) },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('Balsavimas');
  });

  test('a root subject does not render the subject badge', () => {
    const wrapper = mount(ActivityLogEntry, {
      props: { entry: makeEntry({ subject: { type: 'meeting', id: '1', label: 'Posėdis', is_root: true } }) },
      global: { stubs: commonStubs },
    });

    // The subject's own label ("Posėdis") should not appear as a redundant badge
    // when it *is* the root -- only changes/timestamp should render.
    expect(wrapper.findAllComponents({ name: 'Badge' })).toHaveLength(0);
  });

  test('an updated event with changes renders ActivityChangeRow entries', () => {
    const wrapper = mount(ActivityLogEntry, {
      props: {
        entry: makeEntry({
          event: 'updated',
          changes: [
            { key: 'status', label: 'būsena', type: 'text', old: 'open', new: 'resolved', old_display: 'open', new_display: 'resolved' },
          ],
        }),
      },
      global: { stubs: commonStubs },
    });

    expect(wrapper.findComponent({ name: 'ActivityChangeRow' }).exists()).toBe(true);
  });

  test('a relation_updated event renders ActivityRelationChange, not change rows', () => {
    const wrapper = mount(ActivityLogEntry, {
      props: {
        entry: makeEntry({
          event: 'relation_updated',
          changes: [],
          relation_change: {
            relation: 'users',
            label: 'nariai',
            attached: [{ id: '1', label: 'Jonas' }],
            detached: [],
          },
        }),
      },
      global: { stubs: commonStubs },
    });

    expect(wrapper.findComponent({ name: 'ActivityRelationChange' }).exists()).toBe(true);
    expect(wrapper.findComponent({ name: 'ActivityChangeRow' }).exists()).toBe(false);
  });
});
