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
    props: ['title', 'facts', 'sections', 'primaryAction', 'overflowActions', 'actionsBesideTitle'],
    emits: ['action'],
    template: `
      <div>
        <h1><slot name="title">{{ title }}</slot></h1>
        <div v-for="f in facts" :key="f.key" :data-testid="'fact-' + f.key" :data-status="f.status?.label">{{ f.value ?? f.detail }}</div>
        <div data-testid="tabs">{{ sections.map(s => s.label + ':' + (s.count ?? '')).join('|') }}</div>
        <button v-if="primaryAction" data-testid="primary" @click="$emit('action', primaryAction.key)">{{ primaryAction.label }}</button>
        <button v-for="a in overflowActions" :key="a.key" :data-testid="'overflow-' + a.key" @click="$emit('action', a.key)">{{ a.label }}</button>
        <slot name="alert" />
        <slot name="members" />
        <slot name="about" />
        <slot name="activity" />
      </div>
    `,
  },
  ConfirmDialog: ConfirmStub,
  AssignDutyUserSheet: {
    props: ['open', 'dutiable', 'existingTerms', 'studyPrograms'],
    template: '<div data-testid="assign-sheet" :data-open="open" :data-dutiable="dutiable?.id" :data-terms="(existingTerms ?? []).map(t => `${t.user_id}:${t.start_date}`).join(\',\')" />',
  },
  DutiableTimelineDialog: true,
  ExOfficioReviewSheet: true,
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

  it('renders both gender endings in the record heading', () => {
    const wrapper = mount(ShowDuty, {
      props: { duty: baseDuty, can: { update: true, managePeople: true } },
      global: { stubs: { ...stubs, InflectedDutyName: false } },
    });

    expect(wrapper.get('h1 [data-testid="duty-ending-masculine"]').text()).toBe('ius');
    expect(wrapper.get('h1 [data-testid="duty-ending-feminine"]').text()).toBe('ė');
    expect(wrapper.get('h1 [data-testid="duty-ending-underline"]').classes()).toContain('bg-brand');
    wrapper.unmount();
  });

  describe('?dutiable= (redirect from the retired edit page)', () => {
    afterEach(() => {
      window.history.replaceState({}, '', '/');
    });

    it('opens that term in the sheet and drops the parameter', async () => {
      window.history.replaceState({}, '', '/mano/duties/duty-1?dutiable=dutiable-current&tab=members');

      const wrapper = mountPage();
      await wrapper.vm.$nextTick();
      const sheet = wrapper.find('[data-testid="assign-sheet"]');

      expect(sheet.attributes('data-open')).toBe('true');
      expect(sheet.attributes('data-dutiable')).toBe('dutiable-current');
      expect(window.location.search).toBe('?tab=members');
    });

    it('does not open the sheet for someone who cannot manage people', () => {
      window.history.replaceState({}, '', '/mano/duties/duty-1?dutiable=dutiable-current');

      const wrapper = mountPage({ can: { update: true, managePeople: false } });

      expect(wrapper.find('[data-testid="assign-sheet"]').attributes('data-open')).toBe('false');
    });

    it('ignores a term that is not on this duty', () => {
      window.history.replaceState({}, '', '/mano/duties/duty-1?dutiable=nope');

      const wrapper = mountPage();

      expect(wrapper.find('[data-testid="assign-sheet"]').attributes('data-open')).toBe('false');
    });
  });

  it('offers the ex officio review only when people management and links are available', async () => {
    const wrapper = mountPage({ can: { update: true, managePeople: true, reviewExOfficio: true } });
    await wrapper.get('[data-testid="overflow-ex-officio-review"]').trigger('click');
    expect(wrapper.findComponent({ name: 'ExOfficioReviewSheet' }).exists()).toBe(true);
    await wrapper.setProps({ can: { update: true, managePeople: false, reviewExOfficio: true } });
    expect(wrapper.find('[data-testid="overflow-ex-officio-review"]').exists()).toBe(false);
    await wrapper.setProps({ can: { update: true, managePeople: true, reviewExOfficio: false } });
    expect(wrapper.find('[data-testid="overflow-ex-officio-review"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it('paints no status for a duty that is simply occupied', () => {
    const wrapper = mountPage();

    expect(wrapper.text()).toContain('Komunikacijos koordinatorius');
    expect(wrapper.text()).toContain('Dabartinis Narys');
    expect(wrapper.find('[data-testid="fact-members"]').attributes('data-status')).toBeUndefined();
    expect(wrapper.find('[data-testid="duty-vacancy-alert"]').exists()).toBe(false);
  });

  it('flags a vacant duty on the members fact and as the record alert', () => {
    const wrapper = mountPage({ duty: { ...baseDuty, users: [] } });

    expect(wrapper.find('[data-testid="fact-members"]').attributes('data-status')).toBe('Neužimta');
    expect(wrapper.find('[data-testid="fact-members"]').text()).toBe('');
    expect(wrapper.find('[data-testid="duty-vacancy-alert"]').exists()).toBe(true);
  });

  it('marks an inactive duty instead of calling it vacant', () => {
    const wrapper = mountPage({ duty: { ...baseDuty, users: [], is_active: false } });

    expect(wrapper.find('[data-testid="fact-members"]').attributes('data-status')).toBe('Nebeveikianti');
    expect(wrapper.find('[data-testid="fact-members"]').text()).toBe('0');
    expect(wrapper.find('[data-testid="duty-vacancy-alert"]').exists()).toBe(false);
  });

  it('labels an old term with the name the duty had then', () => {
    const wrapper = mountPage({
      duty: {
        ...baseDuty,
        users: [holder('past', 'Buvęs Narys', { start_date: '2019-07-01', end_date: '2020-06-30' })],
        former_names: [{ name: 'Viešųjų ryšių koordinatorius', used_until: '2021-06-30' }],
      },
    });

    expect(wrapper.find('[data-testid="member-term-duty-name"]').text()).toContain('Viešųjų ryšių koordinatorius');
  });

  const combinedLink = { id: 'duty-0', link_id: 7, name: 'Socialinis-akademinis koordinatorius', is_active: false, institution_name: 'VU SA MIF' };

  it('lists the holders of a predecessor duty in the history', () => {
    const wrapper = mountPage({
      lineage: {
        events: [{ kind: 'took_over', date: '2019-07-01', duties: [combinedLink] }],
        suggestedLinkDate: '2019-07-01',
        predecessorTerms: [{
          id: 'old',
          name: 'Senas Narys',
          pivot: { id: 'dutiable-old', start_date: '2018-07-01', end_date: '2019-06-30' },
          duty_name: 'Socialinis-akademinis koordinatorius',
        }],
      },
    });

    expect(wrapper.find('[data-testid="predecessor-term"]').text()).toContain('Senas Narys');
    expect(wrapper.find('[data-testid="duty-lineage"]').text()).toContain('Socialinis-akademinis koordinatorius');
  });

  it('shows renames and takeovers on one dated timeline, folding only where offered', async () => {
    const wrapper = mountPage({
      lineage: {
        events: [
          { kind: 'handed_over', date: '2024-07-01', duties: [{ ...combinedLink, id: 'duty-2', link_id: 9 }, { ...combinedLink, id: 'duty-3', link_id: 10 }] },
          { kind: 'took_over', date: '2019-07-01', duties: [{ ...combinedLink, can_fold: true }] },
          { kind: 'rename', date: '2018-06-30', name: 'Informacijos koordinatorius' },
        ],
        suggestedLinkDate: '2019-07-01',
        predecessorTerms: [],
      },
    });

    expect(wrapper.findAll('[data-slot="duty-lineage-timeline"] > li').map(event => event.attributes('data-testid')))
      .toEqual(['lineage-event-handed_over', 'lineage-event-took_over', 'lineage-event-rename']);
    expect(wrapper.text()).toContain('Informacijos koordinatorius');
    expect(wrapper.findAll('[data-testid="lineage-fold"]')).toHaveLength(1);

    await wrapper.get('[data-testid="lineage-fold"]').trigger('click');
    await wrapper.get('[data-testid="confirm-Paversti pervadinimu"] [data-testid="confirm-yes"]').trigger('click');

    expect(router.post).toHaveBeenCalledWith(expect.stringContaining('duties.predecessors.foldRename'), {}, { preserveScroll: true });
  });

  it('orders the history from the most recent term to the oldest', () => {
    const wrapper = mountPage({
      duty: {
        ...baseDuty,
        users: [
          holder('oldest', 'Seniausias Narys', { start_date: '2018-07-01', end_date: '2019-06-30' }),
          holder('newest', 'Naujausias Narys', { start_date: '2024-07-01', end_date: '2025-06-30' }),
          holder('middle', 'Vidurinis Narys', { start_date: '2021-07-01', end_date: '2022-06-30' }),
        ],
      },
    });

    const names = wrapper.findAll('[data-slot="member-term-row"]').map(row => row.text());

    expect(names.map(text => text.split(/\s/)[0])).toEqual(['Naujausias', 'Vidurinis', 'Seniausias']);
  });

  it('keeps the server order of current members and names a delegated seat\'s padalinys', () => {
    const wrapper = mountPage({
      duty: {
        ...baseDuty,
        users: [
          holder('z', 'Žydrūnas Narys', { start_date: '2026-01-01', tenant_id: 4, tenant: { id: 4, shortname: 'FSF' } }),
          holder('a', 'Agnė Narė', { start_date: '2026-01-01' }),
        ],
      },
    });
    const rows = wrapper.findAll('[data-slot="member-term-row"]');

    expect(rows.map(row => row.text().split(/\s/)[0])).toEqual(['Žydrūnas', 'Agnė']);
    expect(rows[0].text()).toContain('Deleguota · FSF');
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
    expect(text).toContain('Laikotarpių istorija');
    expect(wrapper.findAll('[data-slot="member-term-row"]')).toHaveLength(3);
    // The tab and the members fact count only who serves today.
    expect(wrapper.find('[data-testid="tabs"]').text()).toContain('Nariai:1');
  });

  it('does not count an upcoming member, so the duty can still be vacant', () => {
    const wrapper = mountPage({
      duty: { ...baseDuty, users: [holder('future', 'Būsimas Narys', { start_date: '2026-10-01' })] },
    });

    expect(wrapper.find('[data-testid="fact-members"]').attributes('data-status')).toBe('Neužimta');
    expect(wrapper.find('[data-testid="fact-members"]').text()).toBe('');
  });

  it('names the institution and its padalinys as facts, with the actions beside the title', () => {
    const wrapper = mountPage();

    expect(wrapper.find('[data-testid="fact-institution"]').text()).toBe('VU SA MIF');
    expect(wrapper.find('[data-testid="fact-tenant"]').text()).toBe('MIF');
    expect(wrapper.findComponent(stubs.RecordPage).props('actionsBesideTitle')).toBe('');
  });

  it('hands the sheet every term, so nobody gets two overlapping ones', () => {
    const wrapper = mountPage();
    const sheet = wrapper.find('[data-testid="assign-sheet"]');

    expect(sheet.attributes('data-terms')).toBe('current:2026-01-01');
  });

  it('offers Priskirti as the one primary action, and edit/delete in the overflow', () => {
    const wrapper = mountPage();

    expect(wrapper.find('[data-testid="primary"]').text()).toBe('Priskirti');
    expect(wrapper.find('[data-testid="overflow-edit"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="overflow-delete"]').exists()).toBe(true);
  });

  it('hides every people action without managePeople', () => {
    const wrapper = mountPage({ can: { update: false, managePeople: false } });

    expect(wrapper.find('[data-testid="primary"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="overflow-edit"]').exists()).toBe(false);
    expect(wrapper.findAll('button').some(b => b.text().includes('Užbaigti pareigas'))).toBe(false);
    expect(wrapper.findAll('button').some(b => b.text().includes('Redaguoti'))).toBe(false);
  });

  it('ends a tenure only after a confirmation, with the Vilnius date', async () => {
    const wrapper = mountPage();

    await wrapper.findAll('button').find(b => b.text().includes('Užbaigti pareigas'))!.trigger('click');
    expect(router.patch).not.toHaveBeenCalled();

    await wrapper.find('[data-testid="confirm-Užbaigti pareigas"] [data-testid="confirm-yes"]').trigger('click');

    expect(router.patch).toHaveBeenCalledTimes(1);
    const [url, payload] = vi.mocked(router.patch).mock.calls[0];
    expect(url).toContain('dutiables.update');
    expect(payload).toMatchObject({ end_date: '2026-09-20', acknowledge_access_change: false });
  });

  it('never offers to end an ex-officio term', () => {
    const wrapper = mountPage({
      duty: { ...baseDuty, users: [holder('derived', 'Ex Officio', { start_date: '2026-01-01', via_dutiable_id: 'src' })] },
    });

    expect(wrapper.findAll('button').some(b => b.text().includes('Užbaigti pareigas'))).toBe(false);
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

describe('informational duty seat count', () => {
  it.each([null, undefined])('hides the reference when it is %s', (places) => {
    const wrapper = mountPage({ duty: { ...baseDuty, places_to_occupy: places } });

    expect(wrapper.find('[data-testid="fact-places"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="fact-members"]').text()).toBe('1');
  });

  it.each([1, 2, 38])('shows %s as a separate neutral reference', (places) => {
    const wrapper = mountPage({ duty: { ...baseDuty, places_to_occupy: places } });

    expect(wrapper.find('[data-testid="fact-places"]').text()).toBe(String(places));
    expect(wrapper.find('[data-testid="fact-places"]').attributes('data-status')).toBeUndefined();
    expect(wrapper.find('[data-testid="fact-members"]').text()).toBe('1');
    expect(wrapper.find('[data-testid="duty-vacancy-alert"]').exists()).toBe(false);
  });
});
