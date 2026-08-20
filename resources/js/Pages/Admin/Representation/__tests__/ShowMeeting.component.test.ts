import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

import ShowMeeting from '@/Pages/Admin/Representation/ShowMeeting.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

const stubs = {
  ActivityLogSheet: { template: '<div data-testid="activity-log" />' },
  UsersAvatarGroup: { props: ['users', 'max', 'size'], template: '<div />' },
  MeetingAgendaList: {
    name: 'MeetingAgendaList',
    props: ['agendaItems', 'meetingId', 'editing'],
    template: '<div data-testid="agenda-list" />',
  },
  MeetingNavigationCards: { template: '<div />' },
  DiscussionPanel: { template: '<div />' },
  FileManager: { name: 'FileManager', template: '<div data-testid="file-manager" />' },
  TaskManager: { name: 'TaskManager', template: '<div data-testid="task-manager" />' },
  MeetingForm: { template: '<div />' },
  AddAgendaItemForm: { template: '<div />' },
  AgendaItemsForm: { template: '<div />' },
};

const baseMeeting = {
  id: 'meet1',
  title: 'Senato posėdis',
  start_time: '2026-03-04T10:00:00.000Z',
  institutions: [],
  agenda_items: [{ id: 'a1' }, { id: 'a2' }],
  tasks: [],
  sharepointPath: null,
};

const createWrapper = () =>
  mount(ShowMeeting, {
    props: { meeting: baseMeeting, representatives: [] },
    global: { stubs },
  });

describe('ShowMeeting.vue', () => {
  beforeEach(() => {
    localStorage.clear();
    window.history.replaceState({}, '', '/');
  });

  it('renders a trigger for each tab, with the agenda item count', () => {
    const triggers = createWrapper().findAll('[role="tab"]');

    expect(triggers).toHaveLength(3);
    expect(triggers[0].text()).toContain('2');
  });

  it('lands on the agenda tab by default', () => {
    const wrapper = createWrapper();

    expect(wrapper.find('[data-testid="agenda-list"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="file-manager"]').exists()).toBe(false);
  });

  /**
   * The page owns tab state so it can honour `?tab=`; this asserts that the
   * controlled binding into ShowPageLayout actually drives the rendered panel.
   */
  it('opens the tab named by the ?tab= URL parameter', () => {
    window.history.replaceState({}, '', '/?tab=files');

    const wrapper = createWrapper();

    expect(wrapper.find('[data-testid="file-manager"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="agenda-list"]').exists()).toBe(false);
  });
});
