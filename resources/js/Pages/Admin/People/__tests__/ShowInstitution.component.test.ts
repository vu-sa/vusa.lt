import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { router, usePage, WhenVisible } from '@inertiajs/vue3';
import { defineComponent, h } from 'vue';

import { router as mockRouter } from '@/mocks/inertia.mock';
import ShowInstitution from '@/Pages/Admin/People/ShowInstitution.vue';
import { InstitutionActivityStatus, InstitutionScope } from '@/Types/enums';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { createActionWindowProvider, type ActionWindowContext } from '@/Composables/useActionWindow';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

enableAutoUnmount(afterEach);

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

/** RecordPage is stubbed so the test can read what the page hands it and fire its emits. */
const stubs = {
  RecordPage: {
    name: 'RecordPage',
    props: ['title', 'status', 'facts', 'sections', 'primaryAction', 'overflowActions', 'actionsBesideTitle', 'section'],
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
        <template v-for="section in sections" :key="section.value">
          <slot :name="section.value" />
        </template>
        <slot name="activity" />
      </div>
    `,
  },
  InstitutionOverviewSection: {
    name: 'InstitutionOverviewSection',
    props: ['institution', 'overview', 'duties', 'canManage', 'meetingsTab'],
    emits: ['navigate-tab', 'assign', 'edit-term'],
    template: '<button data-testid="goto-duties" @click="$emit(\'navigate-tab\', \'duties\')" />',
  },
  InstitutionDutiesSection: {
    name: 'InstitutionDutiesSection',
    props: ['duties', 'institutionId', 'canManage'],
    emits: ['assign', 'edit-term'],
    template: '<div data-testid="duties-section" :data-can-manage="canManage" />',
  },
  InstitutionMeetingsList: true,
  UserAvatar: true,
  UsersAvatarGroup: true,
  InstitutionScopeBadge: true,
  CadenceSection: { template: '<div data-testid="cadences" />' },
  SecretariesSection: { template: '<div data-testid="secretaries" />' },
  AssignDutyUserSheet: { name: 'AssignDutyUserSheet', props: ['open', 'duty', 'dutiable'], template: '<div data-testid="assign-sheet" :data-open="open" />' },
  AddCheckInDialog: true,
  ConfirmDialog: true,
  RecordActivity: { template: '<div data-testid="record-activity" />' },
  TaskManager: true,
  FileableFilesPanel: true,
  RelatedInstitutions: true,
  EmptyState: true,
  InstitutionActivityRequestHistory: true,
  RelationshipLinkList: true,
  ProblemSummaryList: true,
};

type PageProps = InstanceType<typeof ShowInstitution>['$props'];
type Institution = PageProps['institution'];
type ActivityStatus = NonNullable<PageProps['overview']['activity_status']>;
type WrapperProps = Partial<Institution> & {
  can?: Partial<PageProps['can']>;
  overview?: Partial<Omit<PageProps['overview'], 'activity_status'>> & { activity_status?: Partial<ActivityStatus> | null };
  management?: PageProps['management'];
  readOnly?: boolean;
  subscription?: PageProps['subscription'];
};

const baseInstitution: Institution = {
  id: 'inst1',
  name: 'Studentų atstovybė',
  is_active: true,
  description: null,
  types: [],
  managers: [],
  secretaries: [],
  sharepointPath: null,
  sharepointFolderUrl: null,
  has_public_meetings: false,
  governance_scope: InstitutionScope.University,
  duties_count: 0,
  meetings_count: 0,
  problems_count: 0,
  related_institutions_count: 0,
  tenant: { id: 1, shortname: 'MIF' },
};

const baseActivityStatus: ActivityStatus = {
  status: InstitutionActivityStatus.Healthy,
  requires_action: false,
  priority: 0,
  periodicity_days: 30,
  effective_days_since_activity: null,
  progress_percentage: null,
  last_activity_type: null,
  last_activity_at: null,
  last_meeting_at: null,
  next_meeting_at: null,
  active_check_in_until: null,
  last_completed_check_in_at: null,
};

const manager = (id: string, name: string): Institution['managers'][number] => ({
  id, name, profile_photo_path: null, profile_photo_focal_point: null,
  pivot: { id: `term-${id}`, start_date: '2026-01-01', end_date: null },
});

const institutionType = (title: string): Institution['types'][number] => ({
  id: 1, title, parent_id: null, description: null, slug: null, extra_attributes: null,
  created_at: null, updated_at: null, deleted_at: null,
  pivot: { institution_id: 'inst1', institution_type_id: 1 },
});

const createWrapper = (props: WrapperProps = {}) => {
  const { can = {}, overview = {}, management = null, readOnly = false, subscription = null, ...institution } = props;
  const { activity_status, ...overviewFields } = overview;

  return mount(ShowInstitution, {
    props: {
      institution: { ...baseInstitution, ...institution },
      overview: {
        activity_status: activity_status === null ? null : { ...baseActivityStatus, ...activity_status },
        current_users: [],
        recentMeetings: [],
        meetings_count: 0,
        meetings_hidden: false,
        ...overviewFields,
      },
      can: {
        update: true, delete: true, recordMeeting: true, reportActivity: true,
        askAboutActivity: false, createProblem: false, manageLinks: false,
        ...can,
      },
      canViewActivityRequests: false,
      incompleteTasksCount: 0,
      relationKinds: [],
      duties: [],
      meetings: [],
      tasks: [],
      relatedInstitutions: [],
      management,
      readOnly,
      subscription,
    },
    global: { stubs },
  });
};

const tabs = (wrapper: ReturnType<typeof mount>) => wrapper.find('[data-testid="tabs"]').text().split('|');

describe('ShowInstitution.vue', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.mocked(router.reload).mockClear();
    vi.mocked(usePage).mockReturnValue({
      ...createMockPage({ auth: { can: { 'meetings.create.padalinys': true } } }),
      url: '/mano/institutions/inst1',
    });
  });

  const statusFact = (wrapper: ReturnType<typeof createWrapper>) =>
    (wrapper.findComponent({ name: 'RecordPage' }).props('facts') as Array<{ key: string; detail?: string; status?: { label: string; role: string } }>)[0];

  it('shows an inactive institution explicitly instead of a healthy meeting status', () => {
    const wrapper = createWrapper({ is_active: false });
    expect(wrapper.text()).toContain('Nebeveikianti institucija');
  });

  it('leads the facts with the activity status, "Aktyvi" included, instead of a title badge', () => {
    const wrapper = createWrapper();

    expect(wrapper.find('[data-testid="status"]').exists()).toBe(false);
    expect(statusFact(wrapper)).toMatchObject({ key: 'status', status: { label: 'Aktyvi', role: 'success' } });
  });

  it('shows the status as not shown when it is withheld with non-public meetings', () => {
    const wrapper = createWrapper({ overview: { activity_status: null } });

    expect(statusFact(wrapper)).toMatchObject({ key: 'status', status: { label: 'Nerodoma', role: 'neutral' }, detail: null });
  });

  it('says under the status what it rests on, as the institution picker does', () => {
    const wrapper = createWrapper({ overview: { activity_status: { status: InstitutionActivityStatus.Healthy, next_meeting_at: '2026-10-15T10:00:00Z' } } });

    expect(statusFact(wrapper)).toMatchObject({ detail: 'action_window.institution.next_meeting' });
  });

  it('tones an overdue institution\'s status card as danger', () => {
    const wrapper = createWrapper({ overview: { activity_status: { status: InstitutionActivityStatus.Overdue } } });

    expect(statusFact(wrapper)).toMatchObject({ status: { label: 'Vėluoja', role: 'danger' } });
  });

  it('offers the sections an editor can act on, and only those', () => {
    expect(tabs(createWrapper())).toEqual(['overview', 'meetings', 'tasks', 'files', 'terms']);
    expect(tabs(createWrapper({ can: { update: false, delete: false } }))).toEqual(['overview', 'meetings', 'tasks', 'files']);
  });

  it('keeps up to three duties in the overview and gives more a tab of their own', () => {
    const few = createWrapper({ duties_count: 3 });
    const overview = few.findComponent({ name: 'InstitutionOverviewSection' });

    expect(tabs(few)).not.toContain('duties');
    expect(overview.props('duties')).toEqual([]);
    expect(overview.props('canManage')).toBe(true);
    expect(tabs(createWrapper({ duties_count: 4 })).slice(0, 2)).toEqual(['overview', 'duties']);
    expect(createWrapper({ duties_count: 4 }).findComponent({ name: 'InstitutionOverviewSection' }).props('duties')).toBeNull();
  });

  it('gives every section an icon', () => {
    const sections = createWrapper({ duties_count: 4, related_institutions_count: 1, problems_count: 1 })
      .findComponent({ name: 'RecordPage' }).props('sections') as Array<{ icon?: unknown }>;

    expect(sections.every(section => section.icon)).toBe(true);
  });

  it('shows a reader outside the institution its public face: meetings only where they are public', () => {
    const readOnly = { readOnly: true, can: { update: false, delete: false, recordMeeting: false, reportActivity: false }, related_institutions_count: 2 };

    expect(tabs(createWrapper({ ...readOnly, has_public_meetings: true }))).toEqual(['overview', 'meetings']);
    expect(tabs(createWrapper({ ...readOnly, has_public_meetings: false }))).toEqual(['overview']);
    expect(tabs(createWrapper({ ...readOnly, duties_count: 5, has_public_meetings: false }))).toEqual(['overview', 'duties']);
    expect(createWrapper(readOnly).find('[data-testid="overflow-timeline"]').exists()).toBe(false);
  });

  it('offers the relations section when there are related institutions, or to someone who can link them', () => {
    expect(tabs(createWrapper())).not.toContain('related');
    expect(tabs(createWrapper({ related_institutions_count: 2 }))).toContain('related');
    expect(tabs(createWrapper({ can: { update: true, delete: true, recordMeeting: true, reportActivity: true, manageLinks: true } }))).toContain('related');
  });

  it('shows the tenant and the member count as key facts', () => {
    const facts = createWrapper().find('[data-testid="facts"]').text().split('|');

    expect(facts).toEqual(expect.arrayContaining(['tenant', 'members']));
  });

  it('shows up to two coordinator names, then uses the expandable avatar group', () => {
    const twoCoordinators = createWrapper({ managers: [
      manager('1', 'Rūta'),
      manager('2', 'Jonas'),
    ] });
    const threeCoordinators = createWrapper({ managers: [
      manager('1', 'Rūta'),
      manager('2', 'Jonas'),
      manager('3', 'Ieva'),
    ] });

    expect(twoCoordinators.findAll('[data-slot="users-fact-list"] li')).toHaveLength(2);
    expect(twoCoordinators.find('users-avatar-group-stub').exists()).toBe(false);
    expect(threeCoordinators.find('[data-slot="users-fact-list"]').exists()).toBe(false);
    expect(threeCoordinators.find('users-avatar-group-stub').attributes('expandable')).toBe('true');
  });

  it('moves the type and governance scope to a fact and shows meeting visibility instead of periodicity', () => {
    const wrapper = createWrapper({
      types: [institutionType('Taryba')],
      governance_scope: InstitutionScope.Vusa,
      has_public_meetings: false,
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
    const facts = createWrapper({ governance_scope: InstitutionScope.University, types: [institutionType('Senatas')] })
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

  it('locks the activity request to this institution when opened from its record', async () => {
    const props = createWrapper({ can: { askAboutActivity: true } }).props();
    let actionWindow!: ActionWindowContext;
    const wrapper = mount(defineComponent({
      setup() {
        actionWindow = createActionWindowProvider();
        return () => h(ShowInstitution, props);
      },
    }), { global: { stubs } });

    await wrapper.get('[data-testid="overflow-ask-activity"]').trigger('click');

    expect(actionWindow.current.value.id).toBe('activity.campaign');
    expect(actionWindow.draft.activityRequest.institutions).toEqual([{ id: 'inst1', name: 'Studentų atstovybė' }]);
    expect(actionWindow.draft.activityRequest.institutionsLocked).toBe(true);
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

  it.each([false, true])('offers follow or unfollow without a separate mute action (followed: %s)', (followed) => {
    const wrapper = createWrapper({ subscription: { is_followed: followed, is_duty_based: false } });

    expect(wrapper.find('[data-testid="overflow-follow"]').text()).toBe(followed ? 'Nebesekti' : 'Sekti');
    expect(wrapper.find('[data-testid="overflow-mute"]').exists()).toBe(false);
  });

  it('passes the per-record permission down to the duties section, not a tenant-agnostic flag', () => {
    expect(createWrapper({ duties_count: 4 }).find('[data-testid="duties-section"]').attributes('data-can-manage')).toBe('true');
    expect(createWrapper({ duties_count: 4, can: { update: false, delete: false } }).find('[data-testid="duties-section"]').attributes('data-can-manage')).toBe('false');
  });

  it('renders the terms and secretaries once the optional management data arrives', () => {
    const before = createWrapper();
    const after = createWrapper({
      management: {
        cadences: [],
        globalCadences: [],
        cadenceDefaults: { default_start_month_day: '07-01', default_end_month_day: '06-30' },
        secretaryRosters: [],
        studyPrograms: [],
      },
    });

    expect(before.find('[data-testid="cadences"]').exists()).toBe(false);
    expect(after.find('[data-testid="cadences"]').exists()).toBe(true);
    expect(after.find('[data-testid="secretaries"]').exists()).toBe(true);
  });

  it.each<[string, string | string[]]>([
    ['duties', 'duties'],
    ['meetings', 'meetings'],
    ['terms', 'management'],
    ['related', ['relatedInstitutions', 'institutionLinks']],
    ['problems', 'problems'],
    ['files', ['files', 'typeFiles']],
    ['tasks', 'tasks'],
    ['activityRequests', 'activityRequests'],
  ])('loads the %s panel through visibility with a skeleton and no repeated polling', async (tab, data) => {
    const wrapper = createWrapper({ duties_count: 4, related_institutions_count: 1, problems_count: 1 });
    await wrapper.setProps({ canViewActivityRequests: true });
    const panel = wrapper.findAllComponents(WhenVisible)
      .find(component => JSON.stringify(component.props('data')) === JSON.stringify(data));

    expect(panel).toBeDefined();
    expect(panel!.props()).toMatchObject({ data, buffer: 0, always: false });
    expect(panel!.vm.$slots.fallback).toBeTypeOf('function');

    wrapper.findComponent({ name: 'RecordPage' }).vm.$emit('update:section', tab);
    await wrapper.vm.$nextTick();

    expect(router.reload).not.toHaveBeenCalled();
  });

  it('does not mount an activity-history visibility loader without permission', () => {
    const wrapper = createWrapper();

    expect(wrapper.findAllComponents(WhenVisible).some(panel => panel.props('data') === 'activityRequests')).toBe(false);
  });

  it.each(['post', 'patch', 'delete'])('keeps loaded panels in the partial response after a %s', (method) => {
    const wrapper = createWrapper();
    const visit = { method, only: [] as string[], except: ['institutionLinks'] };

    mockRouter.__triggerBefore({ detail: { visit } });

    expect(visit.except).toEqual(expect.arrayContaining([
      'institutionLinks', 'problems', 'files', 'typeFiles', 'activityRequests', 'assignmentTerms',
    ]));
    expect(visit.except).not.toContain('duties');
    expect(visit.except).not.toContain('meetings');
    expect(visit.except).not.toContain('tasks');
    expect(visit.except).not.toContain('management');

    wrapper.unmount();
    const afterUnmount = { method: 'patch', only: [] as string[], except: [] as string[] };
    mockRouter.__triggerBefore({ detail: { visit: afterUnmount } });
    expect(afterUnmount.except).toEqual([]);
  });

  it('leaves navigation and explicit partial mutations unchanged', () => {
    createWrapper();
    const navigation = { method: 'get', only: [] as string[], except: [] as string[] };
    const partialMutation = { method: 'post', only: ['subscription'], except: [] as string[] };

    mockRouter.__triggerBefore({ detail: { visit: navigation } });
    mockRouter.__triggerBefore({ detail: { visit: partialMutation } });

    expect(navigation).toEqual({ method: 'get', only: [], except: [] });
    expect(partialMutation).toEqual({ method: 'post', only: ['subscription'], except: [] });
  });

  it('waits for the complete term before mounting its editor', async () => {
    const wrapper = createWrapper({ duties_count: 4 });
    const user = manager('member1', 'Rūta');
    const duty = { id: 'duty1', name: 'Narys', current_users: [user] };
    wrapper.findComponent({ name: 'InstitutionDutiesSection' }).vm.$emit('edit-term', duty, user);
    await wrapper.vm.$nextTick();

    expect(wrapper.find('[data-testid="assign-sheet"]').exists()).toBe(false);
    expect(router.reload).toHaveBeenCalledExactlyOnceWith(expect.objectContaining({ only: ['assignmentTerms'] }));

    const term: NonNullable<PageProps['assignmentTerms']>[number] = {
      id: user.pivot.id, duty_id: duty.id, dutiable_id: user.id, dutiable_type: 'user',
      via_dutiable_id: 'source-term', tenant_id: 1, start_date: '2026-01-01', end_date: null,
      additional_photo: null, additional_photo_focal_point: null, additional_email: 'term@example.test',
      use_original_duty_name: true, description: { lt: 'Aprašymas', en: 'Description' },
      study_program_id: null, study_program_note: null,
      created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z',
    };
    await wrapper.setProps({ assignmentTerms: [term] });
    vi.mocked(router.reload).mock.calls[0]?.[0]?.onSuccess?.(createMockPage());
    await wrapper.vm.$nextTick();

    expect(wrapper.findComponent({ name: 'AssignDutyUserSheet' }).props('dutiable')).toEqual(term);
  });

  it('selects a deep-linked tab so its visibility loader can load it', () => {
    vi.mocked(usePage).mockReturnValue({
      ...createMockPage(), url: '/mano/institutions/inst1?tab=files',
    });

    const wrapper = createWrapper();

    expect(wrapper.findComponent({ name: 'RecordPage' }).props('section')).toBe('files');
    expect(wrapper.findAllComponents(WhenVisible).some(panel => JSON.stringify(panel.props('data')) === JSON.stringify(['files', 'typeFiles']))).toBe(true);
    expect(router.reload).not.toHaveBeenCalled();
  });

  it('ignores a deep link to a tab the reader cannot access', () => {
    vi.mocked(usePage).mockReturnValue({
      ...createMockPage(), url: '/mano/institutions/inst1?tab=files',
    });

    const wrapper = createWrapper({ readOnly: true });

    expect(wrapper.findComponent({ name: 'RecordPage' }).props('section')).toBe('overview');
    expect(router.reload).not.toHaveBeenCalled();
  });
});
