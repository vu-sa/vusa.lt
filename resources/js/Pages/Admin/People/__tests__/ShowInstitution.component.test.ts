import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import ShowInstitution from '@/Pages/Admin/People/ShowInstitution.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

/** RecordPage is stubbed so the test can read what the page hands it and fire its emits. */
const stubs = {
  RecordPage: {
    name: 'RecordPage',
    props: ['title', 'status', 'facts', 'sections', 'primaryAction', 'overflowActions', 'actionsBesideTitle'],
    emits: ['action', 'update:section'],
    template: `
      <div>
        <h1>{{ title }}</h1>
        <div v-if="status" data-testid="status">{{ status.label }}</div>
        <div data-testid="tabs">{{ sections.map(s => s.value).join('|') }}</div>
        <div data-testid="facts">{{ facts.map(f => f.key).join('|') }}</div>
        <slot name="fact-managers" />
        <button v-if="primaryAction" data-testid="primary" @click="$emit('action', primaryAction.key)">{{ primaryAction.label }}</button>
        <button v-for="a in overflowActions" :key="a.key" :data-testid="'overflow-' + a.key" @click="$emit('action', a.key)">{{ a.label }}</button>
        <slot name="subtitle" />
        <slot name="overview" />
        <slot name="duties" />
        <slot name="terms" />
        <slot name="activity" />
      </div>
    `,
  },
  InstitutionOverviewSection: {
    props: ['institution', 'overview'],
    emits: ['navigate-tab'],
    template: '<button data-testid="goto-duties" @click="$emit(\'navigate-tab\', \'duties\')" />',
  },
  InstitutionDutiesSection: {
    props: ['duties', 'institutionId', 'canManage'],
    template: '<div data-testid="duties-section" :data-can-manage="canManage" />',
  },
  InstitutionMeetingsList: true,
  UserAvatar: true,
  UsersAvatarGroup: true,
  InstitutionScopeBadge: true,
  CadenceSection: { template: '<div data-testid="cadences" />' },
  SecretariesSection: { template: '<div data-testid="secretaries" />' },
  AssignDutyUserSheet: { props: ['open', 'duty', 'dutiable'], template: '<div data-testid="assign-sheet" :data-open="open" />' },
  AddCheckInDialog: true,
  ConfirmDialog: true,
  RecordActivity: { template: '<div data-testid="record-activity" />' },
  TaskManager: true,
  FileableFilesPanel: true,
  RelatedInstitutions: true,
  EmptyState: true,
  Deferred: { template: '<div><slot /></div>' },
};

const baseInstitution = {
  id: 'inst1',
  name: 'Studentų atstovybė',
  short_name: 'SA',
  types: [],
  managers: [],
  secretaries: [],
  sharepointPath: null,
  duties_count: 0,
  meetings_count: 0,
  tasks_count: 0,
  related_institutions_count: 0,
  tenant: { id: 1, shortname: 'MIF' },
};

const createWrapper = (props: Record<string, unknown> = {}) => {
  const {
    can = { update: true, delete: true, recordMeeting: true, reportActivity: true },
    overview = {},
    management = null,
    ...institution
  } = props;

  return mount(ShowInstitution, {
    props: {
      institution: { ...baseInstitution, ...institution },
      overview: {
        activity_status: { status: 'healthy' },
        current_users: [],
        duties: [],
        recentMeetings: [],
        meetings_count: 0,
        recentComments: [],
        ...overview,
      },
      can,
      duties: [],
      meetings: [],
      tasks: [],
      relatedInstitutions: [],
      management,
    },
    global: { stubs },
  });
};

const tabs = (wrapper: ReturnType<typeof mount>) => wrapper.find('[data-testid="tabs"]').text().split('|');

describe('ShowInstitution.vue', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.mocked(usePage).mockReturnValue({
      ...createMockPage({ auth: { can: { 'meetings.create.padalinys': true } } }),
      url: '/mano/institutions/inst1',
    });
  });

  const statusFact = (wrapper: ReturnType<typeof createWrapper>) =>
    (wrapper.findComponent({ name: 'RecordPage' }).props('facts') as Array<{ key: string; detail?: string; status?: { label: string; role: string } }>)[0];

  it('leads the facts with the activity status, "Aktyvi" included, instead of a title badge', () => {
    const wrapper = createWrapper();

    expect(wrapper.find('[data-testid="status"]').exists()).toBe(false);
    expect(statusFact(wrapper)).toMatchObject({ key: 'status', status: { label: 'Aktyvi', role: 'success' } });
  });

  it('says under the status what it rests on, as the institution picker does', () => {
    const wrapper = createWrapper({ overview: { activity_status: { status: 'healthy', next_meeting_at: '2026-10-15T10:00:00Z' } } });

    expect(statusFact(wrapper)).toMatchObject({ detail: 'action_window.institution.next_meeting' });
  });

  it('tones an overdue institution\'s status card as danger', () => {
    const wrapper = createWrapper({ overview: { activity_status: { status: 'overdue' } } });

    expect(statusFact(wrapper)).toMatchObject({ status: { label: 'Vėluoja', role: 'danger' } });
  });

  it('offers the sections an editor can act on, and only those', () => {
    expect(tabs(createWrapper())).toEqual(['overview', 'duties', 'meetings', 'terms', 'files', 'tasks']);
    expect(tabs(createWrapper({ can: { update: false, delete: false } }))).toEqual(['overview', 'duties', 'meetings', 'files', 'tasks']);
  });

  it('offers the relations section only when there are related institutions', () => {
    expect(tabs(createWrapper())).not.toContain('related');
    expect(tabs(createWrapper({ related_institutions_count: 2 }))).toContain('related');
  });

  it('shows the tenant and the member count as key facts', () => {
    const facts = createWrapper().find('[data-testid="facts"]').text().split('|');

    expect(facts).toEqual(expect.arrayContaining(['tenant', 'members']));
  });

  it('shows up to two coordinator names, then uses the expandable avatar group', () => {
    const twoCoordinators = createWrapper({ managers: [
      { id: 1, name: 'Rūta' },
      { id: 2, name: 'Jonas' },
    ] });
    const threeCoordinators = createWrapper({ managers: [
      { id: 1, name: 'Rūta' },
      { id: 2, name: 'Jonas' },
      { id: 3, name: 'Ieva' },
    ] });

    expect(twoCoordinators.findAll('[data-slot="users-fact-list"] li')).toHaveLength(2);
    expect(twoCoordinators.find('users-avatar-group-stub').exists()).toBe(false);
    expect(threeCoordinators.find('[data-slot="users-fact-list"]').exists()).toBe(false);
    expect(threeCoordinators.find('users-avatar-group-stub').attributes('expandable')).toBe('true');
  });

  it('moves the type and governance scope to a fact and shows meeting visibility instead of periodicity', () => {
    const wrapper = createWrapper({
      types: [{ title: 'Taryba' }],
      governance_scope: 'vusa',
      has_public_meetings: false,
      meeting_periodicity_days: 30,
    });
    const facts = wrapper.findComponent({ name: 'RecordPage' }).props('facts') as Array<{ key: string; value?: string }>;

    expect(facts.find(fact => fact.key === 'type')).toMatchObject({
      label: 'forms.options.governance_scope_vusa',
      value: 'Taryba',
      surfaceClass: 'bg-brand/5',
    });
    expect(facts[1]?.key).toBe('type');
    expect(facts.find(fact => fact.key === 'visibility')?.value).toBe('Nevieši posėdžiai');
    expect(facts.map(fact => fact.key)).not.toContain('periodicity');
  });

  it('uses the muted burgundy surface for a VU organ', () => {
    const facts = createWrapper({ governance_scope: 'vu', types: [{ title: 'Senatas' }] })
      .findComponent({ name: 'RecordPage' }).props('facts') as Array<{ key: string; label: string; surfaceClass?: string }>;

    expect(facts.find(fact => fact.key === 'type')).toMatchObject({
      label: 'forms.options.governance_scope_vu',
      surfaceClass: 'bg-[#78003F]/10 dark:bg-[#78003F]/25',
    });
  });

  it('puts the activity action beside the title and keeps the rest in the overflow', () => {
    const wrapper = createWrapper();

    expect(wrapper.find('[data-testid="primary"]').text()).toBe('Fiksuoti veiklą');
    expect(wrapper.findComponent({ name: 'RecordPage' }).props('actionsBesideTitle')).toBe('');
    expect(wrapper.find('[data-testid="overflow-edit"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="overflow-timeline"]').exists()).toBe(true);
  });

  it('shows the primary action when either activity option is available', () => {
    expect(createWrapper({ can: { update: false, delete: false, recordMeeting: false, reportActivity: true } })
      .find('[data-testid="primary"]').exists()).toBe(true);
    expect(createWrapper({ can: { update: false, delete: false, recordMeeting: false, reportActivity: false } })
      .find('[data-testid="primary"]').exists()).toBe(false);
  });

  it('hides editing from someone who may not update the institution', () => {
    const wrapper = createWrapper({ can: { update: false, delete: false } });

    expect(wrapper.find('[data-testid="overflow-edit"]').exists()).toBe(false);
  });

  it('passes the per-record permission down to the duties section, not a tenant-agnostic flag', () => {
    expect(createWrapper().find('[data-testid="duties-section"]').attributes('data-can-manage')).toBe('true');
    expect(createWrapper({ can: { update: false, delete: false } }).find('[data-testid="duties-section"]').attributes('data-can-manage')).toBe('false');
  });

  it('renders the terms and secretaries once the deferred management group arrives', () => {
    const before = createWrapper();
    const after = createWrapper({
      management: {
        cadences: [],
        globalCadences: [],
        cadenceDefaults: { default_start_month_day: '07-01', default_end_month_day: '06-30' },
        secretaryRosters: [],
        suggestedSecretaries: [],
        studyPrograms: [],
      },
    });

    expect(before.find('[data-testid="cadences"]').exists()).toBe(false);
    expect(after.find('[data-testid="cadences"]').exists()).toBe(true);
    expect(after.find('[data-testid="secretaries"]').exists()).toBe(true);
  });
});
