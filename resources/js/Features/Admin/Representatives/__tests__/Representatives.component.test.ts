import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { InstitutionActivityStatus } from '@/Types/enums';

import RepresentativeAttentionList from '../RepresentativeAttentionList.vue';
import RepresentativeList from '../RepresentativeList.vue';
import RepresentativeShareBar from '../RepresentativeShareBar.vue';
import type { RepAttentionReason, RepListFilter, RepRow } from '../types';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => vi.mocked(usePage).mockReturnValue(createMockPage()));

function rep(id: string, overrides: Partial<RepRow> = {}, reason: RepAttentionReason | null = null): RepRow {
  return {
    id,
    name: id,
    email: `${id}@vusa.lt`,
    photo: null,
    institutions: [{ id: 'spk', name: 'SPK', tenant: 'VU SA MIF', status: InstitutionActivityStatus.Healthy, lastMeetingAt: null, nextMeetingAt: null }],
    termStart: '2026-09-01',
    incoming: false,
    activity: 'week',
    meetings: { recorded: 0, held: 0 },
    learning: null,
    attention: reason ? { reason, institution: null } : null,
    ...overrides,
  };
}

const ids = (wrapper: ReturnType<typeof mount>) => wrapper.findAll('[data-rep]').map(row => row.attributes('data-rep'));

describe('RepresentativeAttentionList', () => {
  it('shows five reps with their reason and a way to the rest', async () => {
    const reps = Array.from({ length: 7 }, (_, i) => rep(`rep-${i}`, {}, i === 0 ? 'never_logged_in' : 'inactive'));
    const wrapper = mount(RepresentativeAttentionList, { props: { reps } });

    expect(ids(wrapper)).toEqual(['rep-0', 'rep-1', 'rep-2', 'rep-3', 'rep-4']);
    expect(wrapper.get('[data-rep="rep-0"] [data-slot="attention-reason"]').text()).toBe('representatives.attention.reasons.never_logged_in');
    expect(wrapper.get('[data-rep="rep-0"] a[href^="mailto:"]').attributes('href')).toBe('mailto:rep-0@vusa.lt');

    await wrapper.get('[data-slot="representative-attention-more"]').trigger('click');
    expect(wrapper.emitted('showAll')).toHaveLength(1);
  });

  it('hides the overflow button when every rep fits', () => {
    const wrapper = mount(RepresentativeAttentionList, { props: { reps: [rep('only', {}, 'inactive')] } });

    expect(wrapper.find('[data-slot="representative-attention-more"]').exists()).toBe(false);
  });
});

describe('RepresentativeShareBar', () => {
  it('draws only non-empty segments and emits the pressed one', async () => {
    const wrapper = mount(RepresentativeShareBar, { props: {
      segments: [
        { key: 'week', label: 'Week', value: 3, tone: 'success' },
        { key: 'never', label: 'Never', value: 0, tone: 'danger' },
      ],
      activeKey: 'week',
    } });

    expect(wrapper.findAll('[data-segment]').map(segment => segment.attributes('data-segment'))).toEqual(['week']);
    expect(wrapper.get('[data-legend="week"]').attributes('aria-pressed')).toBe('true');

    await wrapper.get('[data-legend="never"]').trigger('click');
    expect(wrapper.emitted('select')).toEqual([['never']]);
  });
});

describe('RepresentativeList', () => {
  const reps = [
    rep('Anna', { activity: 'never' }, 'never_logged_in'),
    rep('Ben', { learning: { essentialDone: 1, essentialTotal: 4, allDone: 1, allTotal: 5 }, meetings: { recorded: 2, held: 3 } }),
    rep('Cleo', { incoming: true, activity: 'never', institutions: [{ id: 'senate', name: 'Senatas', tenant: null, status: InstitutionActivityStatus.Healthy, lastMeetingAt: null, nextMeetingAt: null }] }),
  ];

  it('filters by the bucket the page sets and keeps incoming reps out of activity buckets', async () => {
    const wrapper = mount(RepresentativeList, { props: { reps, filter: 'never' } });
    expect(ids(wrapper)).toEqual(['Anna']);

    await wrapper.setProps({ filter: 'attention' });
    expect(ids(wrapper)).toEqual(['Anna']);

    await wrapper.setProps({ filter: 'incoming' });
    expect(ids(wrapper)).toEqual(['Cleo']);
    expect(wrapper.get('[data-rep="Cleo"]').text()).toContain('representatives.list.starts');
  });

  it('shows when a rep logged in only as a band, never a date', () => {
    const wrapper = mount(RepresentativeList, { props: { reps } });

    expect(wrapper.get('[data-rep="Ben"] [data-slot="rep-login"]').text()).toBe('representatives.list.login.week');
    expect(wrapper.get('[data-rep="Anna"] [data-slot="rep-login"]').text()).toBe('representatives.list.login.never');
  });

  it('shows learning and own-work lines only when there is something to show', () => {
    const wrapper = mount(RepresentativeList, { props: { reps } });

    expect(wrapper.get('[data-rep="Ben"] [role="progressbar"]').attributes('aria-valuenow')).toBe('1');
    expect(wrapper.find('[data-rep="Ben"] [data-slot="rep-meetings"]').exists()).toBe(true);
    expect(wrapper.find('[data-rep="Anna"] [data-slot="rep-learning"]').exists()).toBe(false);
    expect(wrapper.find('[data-rep="Anna"] [data-slot="rep-meetings"]').exists()).toBe(false);
  });

  it('searches names and institutions, and clearing resets the filter too', async () => {
    const wrapper = mount(RepresentativeList, { props: { reps, 'filter': 'all' as RepListFilter, 'onUpdate:filter': (value: RepListFilter) => wrapper.setProps({ filter: value }) } });
    await wrapper.get('input[type="search"]').setValue('SENAT');
    expect(ids(wrapper)).toEqual(['Cleo']);

    await wrapper.get('input[type="search"]').setValue('nobody');
    expect(ids(wrapper)).toEqual([]);
    await wrapper.get('button').trigger('click');
    expect(ids(wrapper)).toEqual(['Anna', 'Ben', 'Cleo']);
  });
});
