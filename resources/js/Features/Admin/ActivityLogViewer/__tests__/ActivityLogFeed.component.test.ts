import { describe, test, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import ActivityLogFeed from '../ActivityLogFeed.vue';

import { commonStubs } from '@/tests/stubs';
import type { ActivityEntry } from '@/Types/activityLog';

function makeEntry(id: number): ActivityEntry {
  return {
    id,
    event: 'created',
    created_at: '2026-01-15T10:00:00Z',
    causer: null,
    subject: { type: 'meeting', id: String(id), label: 'Posėdis', is_root: true },
    changes: [],
  };
}

describe('ActivityLogFeed', () => {
  test('an empty entries array renders the empty state, not a crash', () => {
    const wrapper = mount(ActivityLogFeed, {
      props: { entries: [] },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('activity.empty');
  });

  test('loading renders skeletons instead of entries or the empty state', () => {
    const wrapper = mount(ActivityLogFeed, {
      props: { entries: [], loading: true },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).not.toContain('activity.empty');
    expect(wrapper.findAll('.animate-pulse').length).toBeGreaterThan(0);
  });

  test('renders one ActivityLogEntry per entry', () => {
    const wrapper = mount(ActivityLogFeed, {
      props: { entries: [makeEntry(1), makeEntry(2), makeEntry(3)] },
      global: { stubs: { ...commonStubs, ActivityLogEntry: true } },
    });

    expect(wrapper.findAllComponents({ name: 'ActivityLogEntry' })).toHaveLength(3);
  });

  test('hasMore renders a "load more" button that emits load-more when clicked', async () => {
    const wrapper = mount(ActivityLogFeed, {
      props: { entries: [makeEntry(1)], hasMore: true },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('activity.load_more');

    await wrapper.find('button').trigger('click');

    expect(wrapper.emitted('load-more')).toHaveLength(1);
  });

  test('hasMore=false does not render a "load more" button', () => {
    const wrapper = mount(ActivityLogFeed, {
      props: { entries: [makeEntry(1)], hasMore: false },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).not.toContain('activity.load_more');
  });
});
