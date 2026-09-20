import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';

import ShowUser from '@/Pages/Admin/People/ShowUser.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

/** RecordPage is stubbed so the test can read what the page hands it and fire its emits. */
const stubs = {
  RecordPage: {
    props: ['title', 'sections', 'facts', 'primaryAction', 'overflowActions'],
    emits: ['action'],
    template: `
      <div>
        <h1>{{ title }}</h1>
        <div data-testid="tabs">{{ sections.map(s => s.value + ':' + (s.count ?? '')).join('|') }}</div>
        <div data-testid="facts">{{ facts.map(f => f.key).join('|') }}</div>
        <button v-if="primaryAction" data-testid="primary" @click="$emit('action', primaryAction.key)">{{ primaryAction.label }}</button>
        <button v-for="a in overflowActions" :key="a.key" :data-testid="'overflow-' + a.key" @click="$emit('action', a.key)">{{ a.label }}</button>
        <slot name="identity" />
        <slot name="subtitle" />
        <slot name="alert" />
        <slot name="duties" />
        <slot name="roles" />
        <slot name="activity" />
      </div>
    `,
  },
  UserTermRow: {
    props: ['duty', 'canManage'],
    emits: ['edit', 'end'],
    template: '<div class="term-row" :data-can-manage="canManage" @click="$emit(\'edit\')">{{ duty.name }}</div>',
  },
  AssignDutyUserSheet: {
    props: ['open', 'duty', 'dutiable', 'user'],
    template: '<div data-testid="assign-sheet" :data-open="open" :data-duty="duty?.id" :data-user="user?.id" />',
  },
  SheetForm: { props: ['open', 'title'], template: '<div v-if="open" data-testid="roles-sheet"><slot /></div>' },
  MultiSelect: true,
  ConfirmDialog: {
    props: ['open', 'title'],
    emits: ['confirm'],
    template: '<div v-if="open" :data-testid="`confirm`"><button data-testid="confirm-yes" @click="$emit(\'confirm\')" /></div>',
  },
  ActivityLogSheet: { template: '<div data-testid="activity-log" />' },
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
  roles: [],
  current_duties: [makeDuty()],
  previous_duties: [makeDuty({ id: 'd2', name: 'Kuratorius', pivot: { id: 'p2', start_date: '2022-09-01', end_date: '2023-06-01' } })],
};

const fullCan = { update: true, delete: true, updateRoles: false, managePasswords: false };

const createWrapper = (overrides: Record<string, unknown> = {}, can: Record<string, boolean> = fullCan, extra: Record<string, unknown> = {}) =>
  mount(ShowUser, {
    props: {
      user: { ...baseUser, ...overrides } as never,
      tasks: [],
      taskStats: { total: 10, completed: 0, pending: 10, overdue: 0, autoCompleting: 0 },
      can: can as never,
      ...extra,
    },
    global: { stubs },
  });

const tabs = (wrapper: ReturnType<typeof mount>) => wrapper.find('[data-testid="tabs"]').text();

describe('ShowUser.vue', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.mocked(usePage).mockReturnValue(createMockPage() as never);
    vi.mocked(router.post).mockClear?.();
    vi.mocked(router.get).mockClear?.();
  });

  it('counts duties in the section from both current and previous assignments', () => {
    const wrapper = createWrapper({
      current_duties: [makeDuty(), makeDuty({ id: 'd3', pivot: { id: 'p3', start_date: '2024-07-01' } })],
      previous_duties: [makeDuty({ id: 'd4', pivot: { id: 'p4', start_date: '2020-01-01', end_date: '2021-01-01' } })],
    });

    expect(tabs(wrapper)).toContain('duties:3');
  });

  it('takes the tasks count from the task stats', () => {
    expect(tabs(createWrapper())).toContain('tasks:10');
  });

  it('groups terms by where they sit today, and never lets someone manage a past one', () => {
    const wrapper = createWrapper({
      current_duties: [makeDuty(), makeDuty({ id: 'd5', name: 'Būsima', pivot: { id: 'p5', start_date: '2999-01-01', end_date: null } })],
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

  describe('roles', () => {
    it('shows the roles section to someone who may change roles, even when the person has none', () => {
      expect(tabs(createWrapper())).not.toContain('roles');
      expect(tabs(createWrapper({}, { ...fullCan, updateRoles: true }))).toContain('roles:0');
      expect(tabs(createWrapper({ roles: [{ id: '1', name: 'Editor' }] }))).toContain('roles:1');
    });

    it('lets only a super admin open the role editor', () => {
      const wrapper = createWrapper({ roles: [{ id: '1', name: 'Editor' }] });
      expect(wrapper.text()).not.toContain('Keisti roles');

      const admin = createWrapper({ roles: [{ id: '1', name: 'Editor' }] }, { ...fullCan, updateRoles: true }, { assignment: { studyPrograms: [], roles: [{ id: 1, name: 'Editor' }] } });
      expect(admin.text()).toContain('Keisti roles');
    });
  });
});
