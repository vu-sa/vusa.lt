import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import ShowMeeting from '@/Pages/Admin/Representation/ShowMeeting.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

const stubs = {
  ...commonStubs,
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
  AddAgendaItemForm: { name: 'AddAgendaItemForm', template: '<div data-testid="single-agenda-form" />' },
  AgendaItemsForm: { name: 'AgendaItemsForm', template: '<div data-testid="bulk-agenda-form" />' },
  AnnounceMeetingDialog: { template: '<div />' },
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

const createWrapper = (props: Record<string, unknown> = {}) =>
  mount(ShowMeeting, {
    props: { meeting: baseMeeting, representatives: [], ...props },
    global: { stubs },
  });

describe('ShowMeeting.vue', () => {
  beforeEach(() => {
    localStorage.clear();
    window.history.replaceState({}, '', '/');
  });

  it('renders a trigger for each tab, with the agenda item count', () => {
    // A VU body: agenda, files, tasks. Its paperwork lives in SharePoint, not in documents.
    const triggers = createWrapper().findAll('[role="tab"]');

    expect(triggers).toHaveLength(3);
    expect(triggers[0].text()).toContain('2');
    expect(triggers.map(t => t.text()).join(' ')).not.toContain('Dokumentai');
  });

  it('adds the documents tab only for a VU SA body', () => {
    const triggers = createWrapper({ governanceScope: 'vusa' }).findAll('[role="tab"]');

    expect(triggers).toHaveLength(4);
    expect(triggers.map(t => t.text()).join(' ')).toContain('Dokumentai');
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
  /**
   * The action window creates the meeting server-side and hands the page the
   * dialog to open, so the two `?action=` values must not be interchangeable.
   */
  it('opens the bulk agenda dialog for ?action=add-bulk', async () => {
    vi.useFakeTimers();
    window.history.replaceState({}, '', '/?action=add-bulk');

    const wrapper = createWrapper();
    vi.advanceTimersByTime(200);
    await nextTick();

    expect(wrapper.find('[data-testid="bulk-agenda-form"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="single-agenda-form"]').exists()).toBe(false);

    vi.useRealTimers();
  });

  it('opens the single agenda dialog for ?action=add', async () => {
    vi.useFakeTimers();
    window.history.replaceState({}, '', '/?action=add');

    const wrapper = createWrapper();
    vi.advanceTimersByTime(200);
    await nextTick();

    expect(wrapper.find('[data-testid="single-agenda-form"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="bulk-agenda-form"]').exists()).toBe(false);

    vi.useRealTimers();
  });

  /**
   * Editing is the page's headline action, so it keeps its own labelled button;
   * attaching another institution is rare enough to live in the overflow menu.
   */
  it('labels the edit button and keeps attaching an institution in the menu', () => {
    const wrapper = createWrapper({
      meeting: { ...baseMeeting, institutions: [{ id: 'i1', name: 'VU SA MIF' }] },
    });

    const menu = wrapper.find('[data-testid="dropdown-menu-content"]');

    expect(wrapper.text()).toContain('Redaguoti posėdį');
    expect(menu.text()).toContain('Pridėti instituciją');
    expect(menu.text()).not.toContain('Redaguoti posėdį');
  });

  it('opens the tab named by the ?tab= URL parameter', () => {
    window.history.replaceState({}, '', '/?tab=files');

    const wrapper = createWrapper();

    expect(wrapper.find('[data-testid="file-manager"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="agenda-list"]').exists()).toBe(false);
  });
});
