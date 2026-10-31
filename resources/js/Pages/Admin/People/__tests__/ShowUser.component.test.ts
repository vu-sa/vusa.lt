import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';

import ShowUser from '@/Pages/Admin/People/ShowUser.vue';
import { commonStubs } from '@/tests/stubs';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

/** RecordPage is stubbed so the test can read what the page hands it and fire its emits. */
const stubs = {
  RecordPage: {
    props: ['title', 'sections', 'facts', 'primaryAction', 'overflowActions', 'historySubject'],
    emits: ['action'],
    template: `
      <div>
        <h1>{{ title }}</h1>
        <div data-testid="fact-values">{{ facts.map(f => f.key + '=' + f.value).join('|') }}</div>
        <div data-testid="tabs">{{ sections.map(s => s.value + ':' + (s.count ?? '')).join('|') }}</div>
        <div data-testid="facts">{{ facts.map(f => f.key).join('|') }}</div>
        <div data-testid="history-subject">{{ historySubject ? historySubject.type + ':' + historySubject.id : '' }}</div>
        <button v-if="primaryAction" data-testid="primary" @click="$emit('action', primaryAction.key)">{{ primaryAction.label }}</button>
        <button v-for="a in overflowActions" :key="a.key" :data-testid="'overflow-' + a.key" @click="$emit('action', a.key)">{{ a.label }}</button>
        <slot name="identity" />
        <slot name="subtitle" />
        <slot name="alert" />
        <slot name="duties" />
        <slot name="access" />
      </div>
    `,
  },
  UserTermRow: {
    name: 'UserTermRow',
    props: ['duty', 'canManage'],
    emits: ['edit', 'end'],
    template: '<div class="term-row" :data-can-manage="canManage" @click="$emit(\'edit\')">{{ duty.name }}</div>',
  },
  AssignDutyUserSheet: {
    props: ['open', 'duty', 'dutiable', 'user'],
    template: '<div data-testid="assign-sheet" :data-open="open" :data-duty="duty?.id" :data-user="user?.id" />',
  },
  AccessChangeWarningDialog: true,
  ConfirmDialog: {
    props: ['open', 'title'],
    emits: ['confirm'],
    template: '<div v-if="open" :data-testid="`confirm`"><button data-testid="confirm-yes" @click="$emit(\'confirm\')" /></div>',
  },
  UserAvatar: { template: '<span class="avatar" />' },
  DutiableTimelineDialog: true,
  TaskManager: { template: '<div data-testid="task-manager" />' },
  EmptyState: { props: ['title'], template: '<div data-testid="empty">{{ title }}</div>' },
};

const makeDuty = (overrides: Record<string, unknown> = {}) => ({
  id: 'd1',
  name: 'Koordinatorius',
  places_to_occupy: 1,
  institution: { id: 'i1', name: 'VU SA Filosofijos fakultete', tenant: { shortname: 'VU SA FsF' } },
  pivot: { id: 'p1', start_date: '2024-07-01', end_date: null },
  ...overrides,
});

const baseUser = {
  id: 'u1',
  name: 'Justina Preidytė',
  email: 'justina@example.com',
  phone: '+370 612 77 522',
  pronouns: 'ji/jos',
  show_pronouns: true,
  has_password: true,
  current_duties: [makeDuty()],
  upcoming_duties: [],
  previous_duties: [makeDuty({ id: 'd2', name: 'Kuratorius', pivot: { id: 'p2', start_date: '2022-09-01', end_date: '2023-06-01' } })],
};

const fullCan = { update: true, delete: true, updateSuperAdmin: false, managePasswords: false };

const createWrapper = (overrides: Record<string, unknown> = {}, can: Record<string, boolean> = fullCan, extra: Record<string, unknown> = {}, componentStubs: Record<string, boolean> = {}) =>
  mount(ShowUser, {
    props: {
      user: { ...baseUser, ...overrides } as never,
      tasks: [],
      taskStats: { total: 10, completed: 0, pending: 10, overdue: 0, autoCompleting: 0 },
      can: can as never,
      ...extra,
    },
    global: { stubs: { ...commonStubs, ...stubs, ...componentStubs } },
  });

const tabs = (wrapper: ReturnType<typeof mount>) => wrapper.find('[data-testid="tabs"]').text();

describe('ShowUser.vue', () => {
  it('shows the activity request tab only with its per-record capability', () => {
    expect(tabs(createWrapper({}, { ...fullCan, viewActivityRequests: true }))).toContain('activityRequests:');
    expect(tabs(createWrapper({}, { ...fullCan, viewActivityRequests: false }))).not.toContain('activityRequests:');
  });

  it('keeps ended assignments from inactive institutions in the previous duties group', () => {
    const ended = makeDuty({
      institution: { id: 'inactive', name: 'Neaktyvi institucija', is_active: false },
      pivot: { id: 'closed', start_date: '2024-01-01', end_date: '2024-06-30' },
    });
    const wrapper = createWrapper({ current_duties: [], previous_duties: [ended] });

    expect(wrapper.find('[data-group="current"]').exists()).toBe(false);
    const row = wrapper.get('[data-group="previous"]').findComponent({ name: 'UserTermRow' });
    expect(row.props('duty').institution.name).toBe('Neaktyvi institucija');
    expect(row.props('duty').pivot.end_date).toBe('2024-06-30');
    expect(row.props('canManage')).toBe(false);
  });
  afterEach(() => vi.useRealTimers());

  beforeEach(() => {
    localStorage.clear();
    vi.mocked(usePage).mockReturnValue(createMockPage() as never);
    vi.mocked(router.post).mockClear?.();
    vi.mocked(router.get).mockClear?.();
  });

  it('inflects real current, upcoming, and previous duty rows using the holder and each assignment override', () => {
    const wrapper = mount(ShowUser, {
      props: {
        user: {
          ...baseUser,
          pronouns: { lt: '', en: 'she/her' },
          current_duties: [makeDuty()],
          upcoming_duties: [makeDuty({ id: 'future', pivot: { id: 'future-term', start_date: '2999-01-01', use_original_duty_name: true } })],
          previous_duties: [makeDuty({ id: 'past', name: 'Kuratorius', pivot: { id: 'past-term', start_date: '2020-01-01', end_date: '2021-01-01' } })],
        } as never,
        tasks: [], taskStats: { total: 0, completed: 0, pending: 0, overdue: 0, autoCompleting: 0 },
        can: fullCan,
      },
      global: { stubs: { ...commonStubs, ...stubs, UserTermRow: false } },
    });

    const rows = wrapper.findAll('[data-slot="user-term-row"]');
    expect(rows).toHaveLength(3);
    expect(rows[0].text()).toContain('Koordinatorė');
    expect(rows[1].text()).toContain('Koordinatorius');
    expect(rows[2].text()).toContain('Kuratorė');
    expect(wrapper.find('[data-testid="duty-ending-group"]').exists()).toBe(false);
    wrapper.unmount();
  });

  /** Change history is about the record, so it opens from ⋯ like on every other record page. */
  it('offers the change history from the record menu', () => {
    const wrapper = createWrapper();

    expect(wrapper.find('[data-testid="history-subject"]').text()).toMatch(/^user:/);
  });

  it('counts current, upcoming, and previous assignments', () => {
    const wrapper = createWrapper({
      current_duties: [makeDuty(), makeDuty({ id: 'd3', pivot: { id: 'p3', start_date: '2024-07-01' } })],
      upcoming_duties: [makeDuty({ id: 'd5', pivot: { id: 'p5', start_date: '2999-01-01' } })],
      previous_duties: [makeDuty({ id: 'd4', pivot: { id: 'p4', start_date: '2020-01-01', end_date: '2021-01-01' } })],
    });

    expect(tabs(wrapper)).toContain('duties:4');
  });

  it('shows the years of activity instead of a current-duty count', () => {
    const wrapper = createWrapper({
      current_duties: [makeDuty({ pivot: { id: 'p1', start_date: '2024-07-01' } })],
      upcoming_duties: [],
      previous_duties: [makeDuty({ id: 'd4', pivot: { id: 'p4', start_date: '2020-01-01', end_date: '2021-01-01' } })],
    });

    expect(wrapper.get('[data-testid="fact-values"]').text()).toContain('years=2020–dabar');
    expect(wrapper.get('[data-testid="facts"]').text()).not.toContain('duties');
  });

  it('takes the tasks count from the task stats', () => {
    expect(tabs(createWrapper())).toContain('tasks:10');
  });

  it('keeps an assignment ending today in the current members group', () => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date('2026-09-30T12:00:00Z'));
    const wrapper = createWrapper({
      current_duties: [makeDuty({ name: 'Paskutinė diena', pivot: { id: 'p1', start_date: '2026-01-01', end_date: '2026-09-30' } })],
      previous_duties: [],
    });

    expect(wrapper.find('[data-group="current"]').text()).toContain('Paskutinė diena');
    expect(wrapper.find('[data-group="previous"]').exists()).toBe(false);
  });

  it('groups terms by where they sit today, and never lets someone manage a past one', () => {
    const wrapper = createWrapper({
      upcoming_duties: [makeDuty({ id: 'd5', name: 'Būsima', pivot: { id: 'p5', start_date: '2999-01-01', end_date: null } })],
    });

    expect(wrapper.findAll('[data-group]').map(group => group.attributes('data-group'))).toEqual(['current', 'upcoming', 'previous']);

    const rows = wrapper.findAll('.term-row');
    expect(rows.map(row => row.attributes('data-can-manage'))).toEqual(['true', 'true', 'false']);
  });

  it('opens the sheet for a term with that duty and its term fixed', async () => {
    const wrapper = createWrapper();

    await wrapper.find('.term-row').trigger('click');

    const sheet = wrapper.find('[data-testid="assign-sheet"]');
    expect(sheet.attributes('data-open')).toBe('true');
    expect(sheet.attributes('data-duty')).toBe('d1');
    expect(sheet.attributes('data-user')).toBe('u1');
  });

  it('adds a duty through the same sheet, with only the person fixed', async () => {
    const wrapper = createWrapper();

    await wrapper.find('[data-testid="primary"]').trigger('click');

    const sheet = wrapper.find('[data-testid="assign-sheet"]');
    expect(sheet.attributes('data-open')).toBe('true');
    expect(sheet.attributes('data-duty')).toBeUndefined();
    expect(sheet.attributes('data-user')).toBe('u1');
  });

  it('falls back to an empty state when the member holds no duties', () => {
    const wrapper = createWrapper({ current_duties: [], previous_duties: [] });

    expect(wrapper.findAll('.term-row')).toHaveLength(0);
    expect(wrapper.find('[data-testid="empty"]').text()).toContain('Pareigų nėra');
  });

  it('uses the primary current duty as the subtitle, inflected for the holder', () => {
    // The page locale defaults to 'lt' in the test setup.
    expect(createWrapper().text()).toContain('Koordinatorė');
  });

  it('keeps seats directly beneath their parent in date-ordered blocks and counts every term', () => {
    const parent = makeDuty({ pivot: { id: 'parent', start_date: '2024-01-01' } });
    const next = makeDuty({ id: 'next', pivot: { id: 'next-parent', start_date: '2025-01-01' } });
    const seat = makeDuty({ id: 'seat', pivot: { id: 'child', start_date: '2025-06-01', via_dutiable_id: 'parent' } });
    const nextSeat = makeDuty({ id: 'next-seat', pivot: { id: 'next-child', start_date: '2023-01-01', via_dutiable_id: 'next-parent' } });
    const wrapper = createWrapper({ current_duties: [nextSeat, next, seat, parent], previous_duties: [] }, fullCan, {}, { UserTermRow: false });

    const rows = wrapper.findAllComponents({ name: 'UserTermRow' });
    expect(rows.map(row => row.props('duty').pivot.id)).toEqual(['parent', 'child', 'next-parent', 'next-child']);
    expect(rows.map(row => Boolean(row.props('nested')))).toEqual([false, true, false, true]);
    expect(rows[1].classes()).toContain('border-l');
    expect(rows[1].find('[data-testid="seat-source"]').exists()).toBe(false);
    expect(tabs(wrapper)).toContain('duties:4');
    wrapper.unmount();
  });

  it('labels a standalone seat with its parent duty from another group and leaves an unloaded parent unlabelled', () => {
    const parent = makeDuty({ pivot: { id: 'parent', start_date: '2020-01-01', end_date: '2021-01-01' } });
    const seat = makeDuty({ id: 'seat', pivot: { id: 'child', start_date: '2024-01-01', via_dutiable_id: 'parent' } });
    const unknown = makeDuty({ id: 'unknown', pivot: { id: 'unknown-child', start_date: '2024-02-01', via_dutiable_id: 'unloaded' } });
    const wrapper = createWrapper({ current_duties: [seat, unknown], previous_duties: [parent] }, fullCan, {}, { UserTermRow: false });

    const rows = wrapper.get('[data-group="current"]').findAllComponents({ name: 'UserTermRow' });
    expect(rows[0].props('nested')).toBe(false);
    expect(rows[0].props('sourceLabel')).toBe('Koordinatorė');
    expect(rows[0].find('[data-testid="seat-source"]').exists()).toBe(true);
    expect(rows[1].props('sourceLabel')).toBeUndefined();
    expect(rows[1].find('[data-testid="seat-source"]').exists()).toBe(false);
    expect(tabs(wrapper)).toContain('duties:3');
    wrapper.unmount();
  });

  it('shows ex officio and delegated chips and offers editing without an end action on a seat', () => {
    const parent = makeDuty();
    const seat = makeDuty({ id: 'seat', pivot: { id: 'child', start_date: '2024-01-01', via_dutiable_id: 'p1', tenant_id: 1 } });
    const wrapper = createWrapper({ current_duties: [seat, parent], previous_duties: [] }, fullCan, {}, { UserTermRow: false });

    const rows = wrapper.findAll('[data-slot="user-term-row"]');
    expect(rows[0].text()).toContain('Užbaigti pareigas');
    expect(rows[1].text()).toContain('Ex-officio');
    expect(rows[1].text()).toContain('Deleguota');
    expect(rows[1].text()).toContain('Redaguoti');
    expect(rows[1].text()).not.toContain('Užbaigti pareigas');
    wrapper.unmount();
  });

  describe('actions', () => {
    it('offers editing and the timeline only to someone who may update the person', () => {
      const editable = createWrapper();
      expect(editable.find('[data-testid="overflow-edit"]').exists()).toBe(true);
      expect(editable.find('[data-testid="overflow-timeline"]').exists()).toBe(true);
      expect(editable.find('[data-testid="primary"]').exists()).toBe(true);

      const readOnly = createWrapper({}, { update: false, delete: false });
      expect(readOnly.find('[data-testid="overflow-edit"]').exists()).toBe(false);
      expect(readOnly.find('[data-testid="primary"]').exists()).toBe(false);
    });

    it('keeps the delete entry out of the menu without the delete permission', () => {
      expect(createWrapper({}, { ...fullCan, delete: false }).find('[data-testid="overflow-delete"]').exists()).toBe(false);
      expect(createWrapper().find('[data-testid="overflow-delete"]').exists()).toBe(true);
    });

    it('confirms before deleting the member', async () => {
      const wrapper = createWrapper();

      await wrapper.find('[data-testid="overflow-delete"]').trigger('click');
      expect(vi.mocked(router.delete)).not.toHaveBeenCalled();

      await wrapper.find('[data-testid="confirm-yes"]').trigger('click');

      expect(vi.mocked(router.delete)).toHaveBeenCalledWith('/mocked/users.destroy');
    });
  });

  describe('passwords (super admin)', () => {
    const superCan = { ...fullCan, managePasswords: true };

    it('offers generating a password, and deleting one only when one is set', () => {
      expect(createWrapper({}, superCan).find('[data-testid="overflow-generate-password"]').exists()).toBe(true);
      expect(createWrapper({}, superCan).find('[data-testid="overflow-delete-password"]').exists()).toBe(true);
      expect(createWrapper({ has_password: false }, superCan).find('[data-testid="overflow-delete-password"]').exists()).toBe(false);
    });

    it('never offers password management to anyone else', () => {
      expect(createWrapper().find('[data-testid="overflow-generate-password"]').exists()).toBe(false);
    });

    it('shows a freshly generated password once, from the flash', () => {
      vi.mocked(usePage).mockReturnValue(createMockPage({ flash: { data: 's3cret-Pass' } }) as never);

      expect(createWrapper({}, superCan).find('[data-testid="generated-password"]').exists()).toBe(true);
    });

    it('shows nothing when no password was just generated', () => {
      expect(createWrapper({}, superCan).find('[data-testid="generated-password"]').exists()).toBe(false);
    });
  });

  describe('super admin', () => {
    it('shows the access section to a super admin, or when the person is one', () => {
      expect(tabs(createWrapper())).not.toContain('access');
      expect(tabs(createWrapper({}, { ...fullCan, updateSuperAdmin: true }))).toContain('access');
      expect(tabs(createWrapper({}, fullCan, { isSuperAdmin: true }))).toContain('access');
    });

    it('lets only a super admin flip the switch', () => {
      const toggle = (wrapper: ReturnType<typeof mount>) => wrapper.findComponent({ name: 'FormToggleRow' });

      expect(toggle(createWrapper({}, fullCan, { isSuperAdmin: true })).props('disabled')).toBe(true);
      expect(toggle(createWrapper({}, { ...fullCan, updateSuperAdmin: true })).props('disabled')).toBe(false);
    });

    it('asks before making the person a super admin, then saves', async () => {
      vi.mocked(router.put).mockClear();
      const wrapper = createWrapper({}, { ...fullCan, updateSuperAdmin: true });

      await wrapper.findComponent({ name: 'FormToggleRow' }).vm.$emit('update:modelValue', true);
      expect(router.put).not.toHaveBeenCalled();

      await wrapper.find('[data-testid="confirm-yes"]').trigger('click');

      expect(router.put).toHaveBeenCalledWith('/mocked/users.superAdmin.update', { is_super_admin: true, acknowledge_access_change: false }, { preserveScroll: true });
    });
  });
});
