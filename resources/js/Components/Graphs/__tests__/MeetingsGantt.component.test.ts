import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';

import MeetingsGantt from '../MeetingsGantt.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const institutions = [
  { id: 'senatas', name: 'VU Senatas', tenant_id: '1', types: [{ id: 'council', title: 'Taryba' }, { id: 'senate', title: 'Senatas' }] },
  { id: 'kolegija', name: 'Studijų kolegija', tenant_id: '1', types: [{ id: 'council', title: 'Taryba' }] },
];

const nextFrame = () => new Promise(resolve => requestAnimationFrame(() => resolve(undefined)));

describe('MeetingsGantt', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    localStorage.clear();
    vi.stubGlobal('fetch', vi.fn(async () => ({ ok: true, json: async () => ({ success: true, data: [] }) })));
    vi.stubGlobal('ResizeObserver', class { observe() {} disconnect() {} });
  });

  afterEach(() => {
    wrapper?.unmount();
    vi.unstubAllGlobals();
  });

  async function mountGantt(props: Record<string, unknown> = {}) {
    localStorage.setItem('gantt-settings', JSON.stringify({ groupBy: 'type' }));
    wrapper = mount(MeetingsGantt, {
      attachTo: document.body,
      props: {
        institutions,
        meetings: [
          { id: 'm1', institution_id: 'senatas', start_time: new Date(), completion_status: 'incomplete' },
        ],
        gaps: [],
        ...props,
      },
      global: { stubs: { MeetingsGanttToolbar: true } },
    });
    await flushPromises();
    await nextFrame();
  }

  it('repeats an institution under each of its type sections, with its meetings on each row', async () => {
    await mountGantt();

    const labels = wrapper.findAll('[data-slot="meetings-gantt"] .grid > div').map(el => el.text()).filter(Boolean);
    expect(labels.filter(text => text.includes('VU Senatas'))).toHaveLength(2);
    expect(labels).toContain('Taryba');
    expect(wrapper.findAll('.meeting-icon')).toHaveLength(2);
  });

  it('marks a meeting an open records request asks about and brackets an activity question', async () => {
    const today = new Date();
    const monthAgo = new Date(today.getTime() - 30 * 86400000);
    const inAWeek = new Date(today.getTime() + 7 * 86400000);
    const iso = (date: Date) => date.toISOString().slice(0, 10);

    await mountGantt({
      activityRequests: [
        { institution_id: 'senatas', campaign: 'missing_meetings', period_start: iso(monthAgo), period_end: iso(inAWeek), sent_at: null, expires_at: inAWeek.toISOString(), recipients_count: 1 },
        { institution_id: 'kolegija', campaign: 'activity_confirmation', period_start: iso(monthAgo), period_end: iso(today), sent_at: null, expires_at: inAWeek.toISOString(), recipients_count: 2 },
      ],
    });

    expect(wrapper.findAll('.meeting-request-badge')).toHaveLength(2);
    expect(wrapper.findAll('.activity-request-chip')).toHaveLength(1);
    // The label column says which rows have a request: Senatas twice (two type sections), Kolegija once.
    expect(wrapper.findAll('[data-slot="gantt-row-request"]')).toHaveLength(3);

    await wrapper.setProps({ showActivityRequests: false });
    await nextFrame();

    expect(wrapper.findAll('.meeting-request-badge')).toHaveLength(0);
    expect(wrapper.findAll('.activity-request-chip')).toHaveLength(0);
    expect(wrapper.findAll('[data-slot="gantt-row-request"]')).toHaveLength(0);
  });
});
