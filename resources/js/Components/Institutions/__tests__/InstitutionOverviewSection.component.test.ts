import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import InstitutionOverviewSection from '../InstitutionOverviewSection.vue';

// Stub render-heavy leaf components; their modules are still imported (and thus
// compiled) by the section, so this also acts as a compile smoke-test.
const stubs = {
  UsersAvatarGroup: true,
  InstitutionMeetingsList: true,
};

type InstitutionProp = InstanceType<typeof InstitutionOverviewSection>['$props']['institution'];
type OverviewProp = InstanceType<typeof InstitutionOverviewSection>['$props']['overview'];

const makeInstitution = (overrides: Record<string, unknown> = {}): InstitutionProp => ({
  id: '1',
  name: 'Test Institution',
  short_name: 'TI',
  description: 'A short description.',
  types: [],
  managers: [],
  secretaries: [],
  sharepointPath: null,
  duties_count: 1,
  meetings_count: 0,
  tasks_count: 0,
  related_institutions_count: 0,
  comments_count: 0,
  meeting_periodicity_days: 30,
  ...overrides,
}) as unknown as InstitutionProp;

const makeOverview = (overrides: Record<string, unknown> = {}): OverviewProp => ({
  current_users: [{ id: 1, name: 'Alice' }],
  duties: [{ id: 'd1', name: 'Chair', current_users: [{ id: 1, name: 'Alice' }] }],
  recentMeetings: [],
  meetings_count: 0,
  recentComments: [],
  activity_status: {
    status: 'healthy',
    requires_action: false,
    priority: 0,
    periodicity_days: 30,
    effective_days_since_activity: 10,
    progress_percentage: 33,
    last_activity_type: 'meeting',
    last_activity_at: '2025-11-01T10:00:00.000Z',
    last_meeting_at: '2025-11-01T10:00:00.000Z',
    next_meeting_at: null,
    active_check_in_until: null,
  },
  ...overrides,
}) as unknown as OverviewProp;

describe('InstitutionOverviewSection', () => {
  it('renders the About section when a description is present', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution(), overview: makeOverview() },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Apie');
    expect(wrapper.text()).toContain('A short description.');
  });

  it('hides the About section when there is no description', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution({ description: '' }), overview: makeOverview() },
      global: { stubs },
    });

    expect(wrapper.text()).not.toContain('Apie');
  });

  it('keeps members, tasks and related institutions out of the overview — each has its own section', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution(), overview: makeOverview() },
      global: { stubs },
    });

    expect(wrapper.text()).not.toContain('Alice');
    expect(wrapper.text()).not.toContain('Overdue task');
    expect(wrapper.text()).not.toContain('VU MIF Taryba');
  });

  it('leaves the status and the day counter to the record\'s status card', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution(), overview: makeOverview() },
      global: { stubs },
    });

    expect(wrapper.find('[data-slot="status-badge"]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('10 d. / 30 d.');
    expect(wrapper.text()).not.toContain('Paskutinis susitikimas');
  });

  it('keeps the activity action out of the overview even when overdue', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: {
        institution: makeInstitution(),
        overview: makeOverview({
          activity_status: {
            ...makeOverview().activity_status,
            status: 'overdue',
            requires_action: true,
            priority: 50,
            effective_days_since_activity: 35,
            progress_percentage: 117,
          },
        }),
      },
      global: { stubs },
    });

    expect(wrapper.find('[data-testid="institution-activity"]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('Fiksuoti veiklą');
  });

  it('shows an empty meetings message without another action button', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution(), overview: makeOverview() },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Nėra susitikimų');
    expect(wrapper.text()).not.toContain('Suplanuoti susitikimą');
  });

  it('links to all meetings once there are some', async () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: {
        institution: makeInstitution(),
        overview: makeOverview({
          recentMeetings: [{ id: 'm1', start_time: '2025-11-01T10:00:00.000Z', title: 'Posėdis' }],
        }),
      },
      global: { stubs },
    });

    await wrapper.findAll('button').find(b => b.text().includes('Visi susitikimai'))!.trigger('click');

    expect(wrapper.emitted('navigate-tab')?.[0]).toEqual(['meetings']);
  });

  it('shows the current-term secretaries apart from the managers', () => {
    // A secretary need not hold a duty here, so they must never read as a member (O22).
    const wrapper = mount(InstitutionOverviewSection, {
      props: {
        institution: makeInstitution({ secretaries: [{ id: 'u1', name: 'Rūta', email: null, profile_photo_path: null }] }),
        overview: makeOverview(),
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('secretaries.label');
    expect(wrapper.text()).not.toContain('Koordinatoriai');
  });
});
