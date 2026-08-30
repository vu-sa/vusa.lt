import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import InstitutionOverviewSection from '../InstitutionOverviewSection.vue';

// Stub render-heavy leaf components; their modules are still imported (and thus
// compiled) by the section, so this also acts as a compile smoke-test.
const stubs = {
  UserPopover: true,
  PriorityAlert: true,
  InstitutionMeetingsPreview: true,
  InstitutionDiscussionPreview: true,
};

type InstitutionProp = InstanceType<typeof InstitutionOverviewSection>['$props']['institution'];

const makeInstitution = (overrides: Record<string, unknown> = {}): InstitutionProp => ({
  id: '1',
  name: 'Test Institution',
  short_name: 'TI',
  description: 'A short description.',
  current_users: [{ id: 1, name: 'Alice' }],
  duties: [{ id: 'd1', name: 'Chair', current_users: [{ id: 1, name: 'Alice' }] }],
  meetings: [],
  allTasks: [],
  comments_count: 0,
  recentComments: [],
  relatedInstitutionsFlat: [],
  meeting_periodicity_days: 30,
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
}) as unknown as InstitutionProp;

describe('InstitutionOverviewSection', () => {
  it('renders the About section when a description is present', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution() },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Apie');
    expect(wrapper.text()).toContain('A short description.');
  });

  it('hides the About section when there is no description', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution({ description: '' }) },
      global: { stubs },
    });

    expect(wrapper.text()).not.toContain('Apie');
  });

  it('renders members with the duty they hold', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution() },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Alice');
    expect(wrapper.text()).toContain('Chair');
  });

  it('keeps tasks out of the overview — they live in their own tab', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: {
        institution: makeInstitution({
          allTasks: [{ id: 't1', name: 'Overdue task', completed_at: null }],
        }),
      },
      global: { stubs },
    });

    expect(wrapper.text()).not.toContain('Overdue task');
  });

  it('keeps related institutions out of the overview — they have their own tab', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: {
        institution: makeInstitution({
          relatedInstitutionsFlat: [{ id: 'other', name: 'VU MIF Taryba' }],
        }),
      },
      global: { stubs },
    });

    expect(wrapper.text()).not.toContain('VU MIF Taryba');
  });

  it('folds the last meeting date into the activity highlight', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: {
        institution: makeInstitution({
          meetings: [{ id: 'm1', start_time: '2025-11-01T10:00:00.000Z', title: 'Posėdis' }],
        }),
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Paskutinis susitikimas');
  });

  it('renders the shared backend activity status', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: {
        institution: makeInstitution({
          activity_status: {
            ...makeInstitution().activity_status,
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

    expect(wrapper.text()).toContain('visak.activity.activity_status.overdue');
    expect(wrapper.text()).toContain('35 d. / 30 d.');
  });

  it('links the overflow member count to the duties tab', async () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: {
        institution: makeInstitution({
          current_users: Array.from({ length: 9 }, (_, i) => ({ id: i + 1, name: `Member ${i + 1}` })),
        }),
      },
      global: { stubs },
    });

    const more = wrapper.findAll('button').find(b => b.text().includes('ir dar :count'));
    expect(more).toBeDefined();
    await more!.trigger('click');

    expect(wrapper.emitted('navigate-tab')?.[0]).toEqual(['duties']);
  });

  it('emits navigate-tab when the members action is used', async () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution() },
      global: { stubs },
    });

    const allMembers = wrapper.findAll('button').find(b => b.text().includes('Visi nariai'));
    await allMembers!.trigger('click');

    expect(wrapper.emitted('navigate-tab')?.[0]).toEqual(['duties']);
  });
});
