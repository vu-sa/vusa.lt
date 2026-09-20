import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import ShowDuty from '@/Pages/Admin/People/ShowDuty.vue';
import { commonStubs } from '@/tests/stubs';

const ConfirmStub = {
  props: ['open', 'title', 'description', 'confirmLabel'],
  emits: ['confirm', 'update:open'],
  template: '<div v-if="open" :data-testid="`confirm-${confirmLabel}`"><button type="button" data-testid="confirm-yes" @click="$emit(\'confirm\')" /></div>',
};

const stubs = {
  ...commonStubs,
  RecordPage: {
    props: ['title', 'status', 'facts', 'sections', 'primaryAction', 'overflowActions'],
    emits: ['action'],
    template: `
      <div>
        <h1>{{ title }}</h1>
        <div v-if="status" data-testid="status">{{ status.label }}</div>
        <div data-testid="tabs">{{ sections.map(s => s.label + ':' + (s.count ?? '')).join('|') }}</div>
        <button v-if="primaryAction" data-testid="primary" @click="$emit('action', primaryAction.key)">{{ primaryAction.label }}</button>
        <button v-for="a in overflowActions" :key="a.key" :data-testid="'overflow-' + a.key" @click="$emit('action', a.key)">{{ a.label }}</button>
        <slot name="subtitle" />
        <slot name="alert" />
        <slot name="members" />
        <slot name="about" />
        <slot name="activity" />
      </div>
    `,
  },
  ConfirmDialog: ConfirmStub,
  AssignDutyUserSheet: {
    props: ['open', 'dutiable', 'takenIds', 'occupiedPlaces', 'studyPrograms'],
    template: '<div data-testid="assign-sheet" :data-open="open" :data-taken="(takenIds ?? []).join(\',\')" />',
  },
  DutiableTimelineDialog: true,
  AccessChangeWarningDialog: true,
  UserAvatar: { template: '<span class="avatar" />' },
  InflectedDutyName: { props: ['name'], template: '<span>{{ typeof name === "string" ? name : name?.lt }}</span>' },
  RecordActivity: { template: '<div data-testid="record-activity" />' },
};

const holder = (id: string, name: string, pivot: Record<string, unknown>) => ({
  id,
  name,
  email: `${id}@vusa.lt`,
  pivot: { id: `dutiable-${id}`, duty_id: 'duty-1', dutiable_id: id, end_date: null, ...pivot },
});

const baseDuty = {
  id: 'duty-1',
  name: { lt: 'Komunikacijos koordinatorius', en: 'Communication Coordinator' },
  email: 'komunikacija@vusa.lt',
  places_to_occupy: 2,
  description: '<p>Aprašymas</p>',
  institution: { id: 'inst-1', name: 'VU SA MIF', tenant: { id: 11, shortname: 'MIF' } },
  users: [holder('current', 'Dabartinis Narys', { start_date: '2026-01-01' })],
  types: [{ id: 1, title: 'Koordinatoriai' }],
};

const mountPage = (props: Record<string, unknown> = {}) =>
  mount(ShowDuty, {
    props: { duty: baseDuty, can: { update: true, managePeople: true }, ...props },
    global: { stubs },
  });

describe('ShowDuty.vue', () => {
  beforeEach(() => {
    vi.useFakeTimers({ toFake: ['Date'] });
    vi.setSystemTime(new Date('2026-09-19T22:30:00Z')); // 2026-09-20 in Vilnius
    vi.mocked(router.patch).mockClear?.();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('paints no status for a duty that is simply occupied', () => {
    const wrapper = mountPage();

    expect(wrapper.text()).toContain('Komunikacijos koordinatorius');
    expect(wrapper.text()).toContain('Dabartinis Narys');
    expect(wrapper.find('[data-testid="status"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="duty-vacancy-alert"]').exists()).toBe(false);
  });

  it('flags a vacant duty once as a status and once as the record alert', () => {
    const wrapper = mountPage({ duty: { ...baseDuty, users: [] } });

    expect(wrapper.find('[data-testid="status"]').text()).toBe('Neužimta');
    expect(wrapper.find('[data-testid="duty-vacancy-alert"]').exists()).toBe(true);
  });

  it('splits members into current, upcoming and history by term', () => {
    const wrapper = mountPage({
      duty: {
        ...baseDuty,
        users: [
          holder('current', 'Dabartinis Narys', { start_date: '2026-01-01' }),
          holder('future', 'Būsimas Narys', { start_date: '2026-10-01' }),
          holder('past', 'Buvęs Narys', { start_date: '2024-01-01', end_date: '2025-06-30' }),
        ],
      },
    });

    const text = wrapper.text();
    expect(text).toContain('Būsimi nariai');
    expect(text).toContain('Kadencijų istorija');
    expect(wrapper.findAll('[data-slot="member-term-row"]')).toHaveLength(3);
    // The tab and the places fact count only who serves today.
    expect(wrapper.find('[data-testid="tabs"]').text()).toContain('Nariai:1');
  });

  it('does not count an upcoming member, so the duty can still be vacant', () => {
    const wrapper = mountPage({
      duty: { ...baseDuty, users: [holder('future', 'Būsimas Narys', { start_date: '2026-10-01' })] },
    });

    expect(wrapper.find('[data-testid="status"]').text()).toBe('Neužimta');
  });

  it('hands the sheet who already holds the duty, so they cannot be assigned twice', () => {
    const wrapper = mountPage();
    const sheet = wrapper.find('[data-testid="assign-sheet"]');

    expect(sheet.attributes('data-taken')).toBe('current');
  });

  it('offers Priskirti narį as the one primary action, and edit/delete in the overflow', () => {
    const wrapper = mountPage();

    expect(wrapper.find('[data-testid="primary"]').text()).toBe('Priskirti narį');
    expect(wrapper.find('[data-testid="overflow-edit"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="overflow-delete"]').exists()).toBe(true);
  });

  it('hides every people action without managePeople', () => {
    const wrapper = mountPage({ can: { update: false, managePeople: false } });

    expect(wrapper.find('[data-testid="primary"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="overflow-edit"]').exists()).toBe(false);
    expect(wrapper.findAll('button').some(b => b.text().includes('Baigti kadenciją'))).toBe(false);
    expect(wrapper.findAll('button').some(b => b.text().includes('Redaguoti'))).toBe(false);
  });

  it('ends a tenure only after a confirmation, with the Vilnius date', async () => {
    const wrapper = mountPage();

    await wrapper.findAll('button').find(b => b.text().includes('Baigti kadenciją'))!.trigger('click');
    expect(router.patch).not.toHaveBeenCalled();

    await wrapper.find('[data-testid="confirm-Baigti kadenciją"] [data-testid="confirm-yes"]').trigger('click');

    expect(router.patch).toHaveBeenCalledTimes(1);
    const [url, payload] = vi.mocked(router.patch).mock.calls[0];
    expect(url).toContain('dutiables.update');
    expect(payload).toMatchObject({ end_date: '2026-09-20', acknowledge_access_change: false });
  });

  it('never offers to end an ex-officio term', () => {
    const wrapper = mountPage({
      duty: { ...baseDuty, users: [holder('derived', 'Ex Officio', { start_date: '2026-01-01', via_dutiable_id: 'src' })] },
    });

    expect(wrapper.findAll('button').some(b => b.text().includes('Baigti kadenciją'))).toBe(false);
    expect(wrapper.text()).toContain('Ex-officio');
  });

  it('asks before deleting the duty', async () => {
    const wrapper = mountPage();

    await wrapper.find('[data-testid="overflow-delete"]').trigger('click');
    expect(router.delete).not.toHaveBeenCalled();

    await wrapper.find('[data-testid="confirm-Ištrinti"] [data-testid="confirm-yes"]').trigger('click');
    expect(router.delete).toHaveBeenCalledTimes(1);
  });

  it('lists sibling duties once the deferred panel has loaded', () => {
    const wrapper = mountPage({
      otherDuties: [{ id: 'duty-2', name: { lt: 'Sekretorius' }, current_users: [{ id: 'u' }] }],
    });

    expect(wrapper.text()).toContain('Kitos pareigybės šioje institucijoje');
    expect(wrapper.text()).toContain('Sekretorius');
  });

  it('renders the activity feed', () => {
    expect(mountPage().find('[data-testid="record-activity"]').exists()).toBe(true);
  });
});
