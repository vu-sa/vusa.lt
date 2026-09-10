import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import ShowInstitution from '@/Pages/Admin/People/ShowInstitution.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

/**
 * The overview panel is stubbed so the test can fire `navigate-tab` the way the
 * real InstitutionOverviewSection does — that emit is what the page's controlled
 * `v-model:tab` binding has to honour.
 */
const stubs = {
  InstitutionOverviewSection: {
    name: 'InstitutionOverviewSection',
    props: ['institution', 'overview', 'canEditMembers'],
    emits: ['navigate-tab'],
    template: '<button data-testid="goto-duties" @click="$emit(\'navigate-tab\', \'duties\')" />',
  },
  ActivityLogSheet: { template: '<div data-testid="activity-log" />' },
  UsersAvatarGroup: { props: ['users', 'max', 'size'], template: '<div />' },
  MoreOptionsButton: { template: '<div />' },
  InstitutionMeetingsList: {
    name: 'InstitutionMeetingsList',
    props: ['meetings', 'institutionName', 'canDelete'],
    template: '<div data-testid="meetings-list" />',
  },
  DutySummaryCard: { props: ['duty', 'showInstitution'], template: '<div class="duty-card" />' },
  TaskManager: { template: '<div />' },
  DiscussionPanel: { template: '<div />' },
  SimpleFileViewer: { template: '<div />' },
  FileManager: { template: '<div />' },
  RelatedInstitutions: { template: '<div />' },
  AddCheckInDialog: { template: '<div />' },
};

const baseInstitution = {
  id: 'inst1',
  name: 'Studentų atstovybė',
  short_name: 'SA',
  types: [],
  managers: [],
  administrators: [],
  sharepointPath: null,
  duties_count: 0,
  meetings_count: 0,
  tasks_count: 0,
  related_institutions_count: 0,
};

const createWrapper = (props: Record<string, unknown> = {}) => {
  const {
    duties = [],
    meetings = [],
    tasks = [],
    relatedInstitutions = [],
    overview = {},
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
      duties,
      meetings,
      tasks,
      relatedInstitutions,
    },
    global: { stubs },
  });
};

const tabLabels = (wrapper: ReturnType<typeof mount>) =>
  wrapper.findAll('[role="tab"]').map(tab => tab.text());

describe('ShowInstitution.vue', () => {
  beforeEach(() => {
    localStorage.clear();
    // The page reads `?activityAction=` off page.url, which the shared mock omits.
    vi.mocked(usePage).mockReturnValue({
      ...createMockPage(),
      url: '/mano/institutions/inst1',
    });
  });

  it('shows the current term administrators apart from the managers', () => {
    // An administrator need not hold a duty here, so they must never read as a member.
    const wrapper = createWrapper({
      administrators: [{ id: 'u1', name: 'Rūta Petraitė', email: null, profile_photo_path: null }],
    });

    expect(wrapper.text()).toContain('administrators.label');
  });

  it('keeps the short name out of the hero — it only echoes the full name', () => {
    expect(createWrapper().find('[data-slot="show-page-hero-subtitle"]').exists()).toBe(false);
  });

  it('hides the administrator group when nobody is nominated', () => {
    expect(createWrapper().text()).not.toContain('administrators.label');
  });

  it('omits the related tab when the institution has no related institutions', () => {
    const labels = tabLabels(createWrapper());

    expect(labels).toHaveLength(6);
    expect(labels.join(' ')).not.toContain('Susijusios institucijos');
  });

  it('offers the related tab once related institutions exist', () => {
    const labels = tabLabels(createWrapper({
      related_institutions_count: 1,
    }));

    expect(labels).toHaveLength(7);
    expect(labels.join(' ')).toContain('Susijusios institucijos');
  });

  it('switches panels when the overview section asks to navigate to another tab', async () => {
    const wrapper = createWrapper({
      duties: [{ id: 'd1', name: 'Pirmininkas', order: 1 }],
      duties_count: 1,
    });

    expect(wrapper.find('[data-testid="goto-duties"]').exists()).toBe(true);
    expect(wrapper.find('.duty-card').exists()).toBe(false);

    await wrapper.find('[data-testid="goto-duties"]').trigger('click');

    expect(wrapper.find('.duty-card').exists()).toBe(true);
  });
});
