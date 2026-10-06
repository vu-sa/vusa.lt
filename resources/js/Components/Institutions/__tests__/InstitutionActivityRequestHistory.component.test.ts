import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import { router } from '@inertiajs/vue3';

import InstitutionActivityRequestHistory from '../InstitutionActivityRequestHistory.vue';
import type { ActivityRequestHistory, ActivityRequestHistoryItem } from '../InstitutionActivityRequestHistory.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const request = (overrides: Partial<ActivityRequestHistoryItem> = {}): ActivityRequestHistoryItem => ({
  id: crypto.randomUUID(),
  recipient: 'Ieva Petraitytė',
  requester: 'Greta Urbonaitė',
  period_start: '2026-09-06',
  period_end: '2026-10-06',
  note: 'Ar vyko posėdis?',
  status: 'pending',
  answer: null,
  created_at: '2026-10-05T18:59:00Z',
  answered_at: null,
  resolved_at: null,
  resolution_source: null,
  resolved_by: null,
  expires_at: '2026-10-19T18:59:00Z',
  meetings: [],
  check_ins: [],
  ...overrides,
});

const batch = (id: string, requests: ActivityRequestHistoryItem[] = [request()]): ActivityRequestHistory['data'][number] => ({ id, campaigns: { activity_confirmation: requests } });

const mountHistory = (history: ActivityRequestHistory) => mount(InstitutionActivityRequestHistory, {
  props: { history },
  global: { stubs: commonStubs },
});

describe('institution request history', () => {
  it('loads only the next history page and appends batches without duplicating them', async () => {
    const wrapper = mountHistory({ data: [batch('first')], next_page: 2 });
    await wrapper.find('button').trigger('click');
    expect(router.reload).toHaveBeenCalledWith(expect.objectContaining({ only: ['activityRequests'], data: { activity_requests_page: 2 } }));
    await wrapper.setProps({ history: { data: [batch('second')], next_page: null } });
    expect(wrapper.findAll('[data-slot=activity-request-sending]')).toHaveLength(2);
    await wrapper.setProps({ history: { data: [batch('second')], next_page: null } });
    expect(wrapper.findAll('[data-slot=activity-request-sending]')).toHaveLength(2);
    expect(wrapper.find('button').exists()).toBe(false);
    await wrapper.setProps({ history: { data: [batch('refreshed')], next_page: 2 } });
    expect(wrapper.findAll('[data-slot=activity-request-sending]')).toHaveLength(1);
  });

  it('heads one sending with its shared note and lists each recipient with a status', () => {
    const wrapper = mountHistory({
      data: [batch('first', [
        request({ recipient: 'Ieva Petraitytė' }),
        request({ recipient: 'Jonas Jonaitis', status: 'answered', answer: 'met', answered_at: '2026-10-06T08:00:00Z', meetings: [{ id: 'm1', date: '2026-10-01', url: '/mano/meetings/m1' }] }),
      ])],
      next_page: null,
    });

    expect(wrapper.findAll('blockquote')).toHaveLength(1);
    const recipients = wrapper.findAll('[data-slot=activity-request-recipient]');
    expect(recipients).toHaveLength(2);
    expect(recipients[0].find('[data-slot=status-badge]').attributes('data-status-role')).toBe('attention');
    expect(recipients[1].find('[data-slot=status-badge]').attributes('data-status-role')).toBe('success');
    expect(recipients[1].find('a').attributes('href')).toBe('/mano/meetings/m1');
  });

  it('names the colleague whose answer closed a recipient question', () => {
    const wrapper = mountHistory({
      data: [batch('first', [
        request({ status: 'resolved', resolved_at: '2026-10-06T08:00:00Z', resolution_source: 'meeting', resolved_by: 'Jonas Jonaitis' }),
        request({ status: 'resolved', resolved_at: '2026-10-06T08:00:00Z', resolution_source: 'meeting' }),
      ])],
      next_page: null,
    });

    const recipients = wrapper.findAll('[data-slot=activity-request-recipient]');
    expect(recipients[0].text()).toContain('activity_requests.history.resolved_by');
    expect(recipients[1].text()).not.toContain('activity_requests.history.resolved_by');
  });
});
