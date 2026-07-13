import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import InstitutionOverviewSection from '../InstitutionOverviewSection.vue';

// Stub render-heavy leaf components; their modules are still imported (and thus
// compiled) by the section, so this also acts as a compile smoke-test.
const stubs = {
  UserPopover: true,
  PriorityAlert: true,
  InstitutionMeetingsPreview: true,
  InstitutionTasksPreview: true,
  InstitutionDiscussionPreview: true,
  InstitutionRelatedPreview: true,
};

const makeInstitution = (overrides: Record<string, unknown> = {}) => ({
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
  ...overrides,
});

describe('InstitutionOverviewSection', () => {
  it('renders the About section when a description is present', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution() as any },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Apie');
    expect(wrapper.text()).toContain('A short description.');
  });

  it('hides the About section when there is no description', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution({ description: '' }) as any },
      global: { stubs },
    });

    expect(wrapper.text()).not.toContain('Apie');
  });

  it('renders members with their duty role badge', () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution() as any },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Alice');
    expect(wrapper.text()).toContain('Chair');
  });

  it('emits navigate-tab when the members action is used', async () => {
    const wrapper = mount(InstitutionOverviewSection, {
      props: { institution: makeInstitution() as any },
      global: { stubs },
    });

    const allMembers = wrapper.findAll('button').find((b) => b.text().includes('Visi nariai'));
    await allMembers!.trigger('click');

    expect(wrapper.emitted('navigate-tab')?.[0]).toEqual(['duties']);
  });
});
