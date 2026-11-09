import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import InstitutionOverviewSection from '../InstitutionOverviewSection.vue';

// Stub render-heavy leaf components; their modules are still imported (and thus
// compiled) by the section, so this also acts as a compile smoke-test.
const stubs = {
  UsersAvatarGroup: true,
  InstitutionDutiesSection: { props: ['duties'], template: '<div data-testid="duties">{{ duties.length }}</div>' },
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

    // An empty section is one line (visual budget), with no second way to plan a meeting.
    expect(wrapper.text()).toContain('Šiai institucijai dar nėra suplanuota susitikimų.');
    expect(wrapper.find('[data-slot="institution-recent-meetings"]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('Suplanuoti susitikimą');
  });

  it('links to all meetings once there are some', async () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: {
        institution: makeInstitution(),
        overview: makeOverview({
          recentMeetings: [{ id: 'm1', start_time: '2025-11-01T10:00:00.000Z', title: 'Posėdis' }],
        }),
        meetingsTab: true,
      },
      global: { stubs },
    });

    await wrapper.findAll('button').find(b => b.text().includes('Visi posėdžiai'))!.trigger('click');

    expect(wrapper.emitted('navigate-tab')?.[0]).toEqual(['meetings']);
  });

  it('shows the three newest meetings as cards', () => {
    const meeting = (id: string, start: string) => ({ id, start_time: start, title: `${id} posėdis` });
    const wrapper = mount(InstitutionOverviewSection, {
      props: {
        institution: makeInstitution(),
        overview: makeOverview({
          recentMeetings: [
            meeting('old', '2025-01-01T10:00:00.000Z'),
            meeting('newest', '2025-11-01T10:00:00.000Z'),
            meeting('middle', '2025-06-01T10:00:00.000Z'),
            meeting('older', '2025-03-01T10:00:00.000Z'),
          ],
        }),
      },
      global: { stubs },
    });

    const cards = wrapper.findAll('[data-slot="meeting-card"]');
    expect(cards.map(card => card.text().match(/([a-z]+) posėdis/)?.[1])).toEqual(['newest', 'middle', 'older']);
  });

  it('shows the duties only when the page hands them to the overview', () => {
    const duties = [{ id: 'd1', name: 'Pirmininkas', current_users: [] }];
    const without = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution(), overview: makeOverview() },
      global: { stubs },
    });
    const withDuties = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution(), overview: makeOverview(), duties },
      global: { stubs },
    });

    expect(without.find('[data-testid="duties"]').exists()).toBe(false);
    expect(withDuties.get('[data-testid="duties"]').text()).toBe('1');
  });

  it('shows current-term secretaries without repeating coordinators from the key facts', () => {
    // A secretary need not hold a duty here, so they must never read as a member (O22).
    const wrapper = mount(InstitutionOverviewSection, {
      props: {
        institution: makeInstitution({
          managers: [{ id: 'u2', name: 'Jonas', email: null, profile_photo_path: null }],
          secretaries: [{ id: 'u1', name: 'Rūta', email: null, profile_photo_path: null }],
        }),
        overview: makeOverview(),
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('secretaries.label');
    expect(wrapper.text()).not.toContain('Koordinatoriai');
  });
});

describe('InstitutionOverviewSection — non-public meetings', () => {
  it('tells a reader without access that the meetings exist but are not public', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution(), overview: makeOverview({ meetings_hidden: true }) },
      global: { stubs },
    });

    expect(wrapper.find('[data-testid="institution-meetings-hidden"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Posėdžiai nėra vieši');
  });

  it('keeps the plain empty state when there simply are no meetings', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution(), overview: makeOverview() },
      global: { stubs },
    });

    expect(wrapper.find('[data-testid="institution-meetings-hidden"]').exists()).toBe(false);
  });
});
