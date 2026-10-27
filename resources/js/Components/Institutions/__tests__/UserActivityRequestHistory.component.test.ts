import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { router } from '@inertiajs/vue3';

import UserActivityRequestHistory from '../UserActivityRequestHistory.vue';
import ActivityRequestHistoryRow from '../ActivityRequestHistoryRow.vue';
import type { ActivityRequestHistoryItem } from '../InstitutionActivityRequestHistory.vue';

import MyActivityRequests from '@/Components/Home/MyActivityRequests.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
const { dismiss } = vi.hoisted(() => ({ dismiss: vi.fn() }));
vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({ isDismissed: { value: false }, dismiss }),
}));

const request = (id: string, overrides: Partial<ActivityRequestHistoryItem> = {}): ActivityRequestHistoryItem => ({
  id, recipient: 'Ieva', requester: 'Greta', campaign_type: 'activity_confirmation',
  institution: { id: 'i1', name: 'Senatas', url: null },
  period_start: '2026-09-01', period_end: '2026-10-01', note: 'Ar vyko posėdis?',
  status: 'pending', answer: null, created_at: '2026-10-01T10:00:00Z',
  answered_at: null, resolved_at: null, resolution_source: null, resolved_by: null,
  expires_at: '2026-10-15T10:00:00Z', meetings: [], check_ins: [], answer_url: null,
  ...overrides,
});

beforeEach(() => vi.clearAllMocks());

describe('received activity requests', () => {
  it('appends each page once and resets when page one is refreshed', async () => {
    const wrapper = mount(UserActivityRequestHistory, {
      props: { history: { data: [request('first')], page: 1, next_page: 2 } },
      global: { stubs: commonStubs },
    });
    await wrapper.get('button').trigger('click');
    expect(router.reload).toHaveBeenCalledWith(expect.objectContaining({ only: ['activityRequests'], data: { activity_requests_page: 2 } }));
    await wrapper.setProps({ history: { data: [request('second')], page: 2, next_page: null } });
    expect(wrapper.findAll('[data-slot=activity-request-recipient]')).toHaveLength(2);
    await wrapper.setProps({ history: { data: [request('second')], page: 2, next_page: null } });
    expect(wrapper.findAll('[data-slot=activity-request-recipient]')).toHaveLength(2);
    await wrapper.setProps({ history: { data: [request('refreshed')], page: 1, next_page: null } });
    expect(wrapper.findAll('[data-slot=activity-request-recipient]')).toHaveLength(1);
  });

  it('shows an explanatory empty state', () => {
    const wrapper = mount(UserActivityRequestHistory, {
      props: { history: { data: [], page: 1, next_page: null } }, global: { stubs: commonStubs },
    });
    expect(wrapper.find('[data-slot=empty-state]').exists()).toBe(true);
    expect(wrapper.find('button').exists()).toBe(false);
  });

  it('shows institution context and only provided answer and result links', async () => {
    const wrapper = mount(ActivityRequestHistoryRow, {
      props: { request: request('one', { answer_url: '/signed-answer', meetings: [{ id: 'm1', date: '2026-09-10', url: null }] }) },
      global: { stubs: commonStubs },
    });
    expect(wrapper.text()).toContain('Senatas');
    expect(wrapper.text()).toContain('Ar vyko posėdis?');
    expect(wrapper.findAll('a')).toHaveLength(1);
    await wrapper.get('a').trigger('click');
    expect(wrapper.emitted('answer')).toHaveLength(1);
    await wrapper.setProps({ request: request('one', { status: 'expired' }) });
    expect(wrapper.find('a').exists()).toBe(false);
    expect(wrapper.get('[data-slot=status-badge]').attributes('data-status-role')).toBe('neutral');
  });

  it('hides the home section without requests and offers valid requests without a representation gate', async () => {
    const wrapper = mount(MyActivityRequests, {
      props: { requests: { total: 0, data: [] } }, global: { stubs: commonStubs },
    });
    expect(wrapper.find('[data-slot=home-activity-requests]').exists()).toBe(false);
    await wrapper.setProps({ requests: { total: 4, data: [{
      id: 'one', institution_name: 'Senatas', campaign_type: 'activity_confirmation',
      period_start: '2026-09-01', period_end: null, expires_at: '2026-10-15T10:00:00Z', answer_url: '/signed-answer',
    }] } });
    expect(wrapper.findAll('li')).toHaveLength(1);
    const answer = wrapper.get('a[href="/signed-answer"]');
    await answer.trigger('click');
    expect(dismiss).toHaveBeenCalledOnce();
    const more = wrapper.findAll('a').find(link => link.attributes('href')?.includes('profile.activityRequests'));
    expect(more?.exists()).toBe(true);
    await more?.trigger('click');
    expect(dismiss).toHaveBeenCalledTimes(2);
  });
});
