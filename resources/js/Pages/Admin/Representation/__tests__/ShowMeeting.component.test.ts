import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import ShowMeeting from '@/Pages/Admin/Representation/ShowMeeting.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string, params?: unknown) => (name === undefined
  ? { current: () => false }
  : `/mocked/${name}${params ? `?${new URLSearchParams(params as Record<string, string>).toString()}` : ''}`));

const stubs = {
  ...commonStubs,
  ActivityLogSheet: { template: '<div data-testid="activity-log" />' },
  UsersAvatarGroup: { props: ['users', 'max', 'size'], template: '<div data-testid="users-avatar-group" :data-user-ids="users.map(user => user.id).join(\',\')" />' },
  MeetingAgendaList: {
    name: 'MeetingAgendaList',
    props: ['agendaItems', 'meetingId', 'missingActions'],
    template: '<div data-testid="agenda-list" />',
  },
  MeetingNavigationCards: { template: '<div />' },
  DiscussionPanel: { template: '<div />' },
  FileableFilesPanel: { name: 'FileableFilesPanel', template: '<div data-testid="files-panel" />' },
  TaskManager: { name: 'TaskManager', template: '<div data-testid="task-manager" />' },
  MeetingForm: { template: '<div />' },
  AddAgendaItemsSheet: {
    name: 'AddAgendaItemsSheet',
    props: { open: Boolean, initialMode: String, meetingId: String, recentAgendas: Array },
    template: '<div data-testid="add-agenda-sheet" :data-open="open" :data-mode="initialMode" />',
  },
  AnnounceMeetingDialog: { template: '<div />' },
  RecordActivity: { template: '<div data-testid="record-activity" />' },
};

const baseMeeting = {
  id: 'meet1',
  title: 'Senato posėdis',
  start_time: '2026-03-04T10:00:00.000Z',
  institutions: [],
  agenda_items: [{ id: 'a1' }, { id: 'a2' }],
  sharepointPath: null,
};

const createWrapper = (props: Record<string, unknown> = {}) =>
  mount(ShowMeeting, {
    props: {
      meeting: baseMeeting,
      representatives: [],
      secretaries: [],
      abilities: {
        update: true,
        delete: true,
        createAgendaItems: true,
        reorderAgendaItems: true,
        attachInstitution: true,
      },
      completion: { status: 'complete', missingActions: [] },
      ...props,
    },
    global: { stubs },
  });

describe('ShowMeeting.vue', () => {
  beforeEach(() => {
    localStorage.clear();
    window.history.replaceState({}, '', '/');
  });

  it('shows only representatives in the people fact', () => {
    const wrapper = createWrapper({
      representatives: [{ id: 'u1', name: 'Jonas Jonaitis' }],
      secretaries: [{ id: 'u2', name: 'Rūta Petraitė', email: null, profile_photo_path: null }],
    });

    const peopleFact = wrapper.findAll('dl > div')[4];

    expect(peopleFact.find('dt').text()).toBe('Atstovai');
    expect(peopleFact.find('[data-slot="users-fact-list"]').text()).toContain('Jonas Jonaitis');
    expect(peopleFact.find('[data-testid="users-avatar-group"]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('secretaries.label');
  });

  it('keeps the expandable avatar group for multiple representatives', () => {
    const peopleFact = createWrapper({
      representatives: [{ id: 'u1', name: 'Jonas Jonaitis' }, { id: 'u2', name: 'Rūta Petraitė' }],
    }).findAll('dl > div')[4];

    expect(peopleFact.find('[data-testid="users-avatar-group"]').attributes('data-user-ids')).toBe('u1,u2');
  });

  it('shows an empty people fact when there are no representatives', () => {
    expect(createWrapper().findAll('dl > div')[4].find('dd').text()).toBe('—');
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

  /**
   * "Paskelbti" and "Atsieti" are not two halves of one condition: a body VU SA only
   * delegates into has nothing to announce, and with no event nothing to unlink either.
   * The v-else offered unlinking on every such meeting.
   */
  describe('the calendar menu item', () => {
    const menuText = (props: Record<string, unknown> = {}) =>
      createWrapper(props).find('[data-testid="dropdown-menu-content"]').text();

    it('offers neither option for an external body with no announcement', () => {
      const text = menuText();

      expect(text).not.toContain('Atsieti nuo kalendoriaus');
      expect(text).not.toContain('Paskelbti kalendoriuje');
    });

    it('offers announcing for a VU SA body with no announcement', () => {
      const text = menuText({ governanceScope: 'vusa' });

      expect(text).toContain('Paskelbti kalendoriuje');
      expect(text).not.toContain('Atsieti nuo kalendoriaus');
    });

    it('offers unlinking once an announcement exists, whatever the body', () => {
      const announced = { ...baseMeeting, calendar_event: { id: 7, is_draft: true } };

      expect(menuText({ meeting: announced })).toContain('Atsieti nuo kalendoriaus');
      expect(menuText({ meeting: announced, governanceScope: 'vusa' }))
        .toContain('Atsieti nuo kalendoriaus');
    });
  });

  it('lands on the agenda tab by default', () => {
    const wrapper = createWrapper();

    expect(wrapper.find('[data-testid="agenda-list"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="agenda-list"]').element.closest('section')?.className).toContain('md:block');
    expect(wrapper.find('[data-testid="files-panel"]').element.closest('section')?.className).toContain('md:hidden');
  });

  /**
   * The action window creates the meeting server-side and hands the page the
   * sheet to open, so the two `?action=` values must not be interchangeable.
   */
  it('opens the add sheet on the paste box for ?action=add-bulk', async () => {
    window.history.replaceState({}, '', '/?action=add-bulk');

    const wrapper = createWrapper();
    await vi.waitFor(() => {
      const sheet = wrapper.find('[data-testid="add-agenda-sheet"]');
      expect(sheet.attributes('data-open')).toBe('true');
      expect(sheet.attributes('data-mode')).toBe('paste');
    });
  });

  it('opens the add sheet on the line editor for ?action=add', async () => {
    window.history.replaceState({}, '', '/?action=add');

    const wrapper = createWrapper();
    await vi.waitFor(() => {
      const sheet = wrapper.find('[data-testid="add-agenda-sheet"]');
      expect(sheet.attributes('data-open')).toBe('true');
      expect(sheet.attributes('data-mode')).toBe('lines');
    });
  });

  it('leads the facts with the completion status, toned in its role, instead of a title badge', () => {
    const wrapper = createWrapper({ completion: { status: 'incomplete', missingActions: [] } });
    const first = wrapper.find('dl > div');

    expect(first.find('dt').text()).toBe('Būsena');
    expect(first.attributes('data-status-role')).toBe('attention');
    expect(first.find('dd').text()).toBe('Neužpildyta');
    expect(wrapper.find('header [data-slot="status-badge"]').exists()).toBe(false);
  });

  it('links each institution fact to the institution page instead of repeating it under the title', () => {
    const wrapper = createWrapper({
      meeting: { ...baseMeeting, type_label: 'Nuotolinis', institutions: [{ id: 'i1', name: 'VU SA MIF' }] },
    });

    const link = wrapper.findAll('a').find(anchor => anchor.text() === 'VU SA MIF');

    expect(link?.attributes('href')).toContain('institutions.show');
    expect(wrapper.find('[data-slot="record-eyebrow"]').text()).toContain('Nuotolinis');
    expect(wrapper.find('[data-slot="record-title"]').text()).not.toContain('10:00');
  });

  it('shows relative and clock time together after visibility', () => {
    const facts = createWrapper().findAll('dl > div');

    expect(facts[2].find('dt').text()).toBe('Laikas');
    expect(facts[2].find('dd').text()).toMatch(/prieš .+ · 10:00/);
  });

  it('shows visibility next to status with a globe and a surface matching public access', () => {
    const publicFact = createWrapper({ publicUrl: 'https://www.vusa.test/lt/meeting' }).findAll('dl > div')[1];
    const privateFact = createWrapper().findAll('dl > div')[1];

    expect(publicFact.classes()).toContain('bg-status-success-surface');
    expect(publicFact.find('dt').attributes('aria-label')).toBe('Matomumas');
    expect(publicFact.find('dt svg').exists()).toBe(true);
    expect(publicFact.find('dt').text()).toBe('');
    expect(publicFact.find('dd').text()).toBe('Matoma vusa.lt');
    expect(privateFact.classes()).toContain('bg-status-neutral-surface');
    expect(privateFact.find('dd').text()).toBe('Tik viduje');
  });

  it('merges the protocol and report into one fact', () => {
    const fact = createWrapper({ meeting: { ...baseMeeting, has_protocol: true } }).findAll('dl > div')[5];
    const statuses = fact.findAll('dd [data-status-role]');

    expect(fact.find('dt').text()).toBe('meetings.record.after_meeting');
    expect(fact.find('dd > div').classes()).toContain('text-xs');
    expect(statuses[0].findAll('span')[0].text()).toBe('meetings.record.protocol');
    expect(statuses[1].findAll('span')[0].text()).toBe('meetings.record.report');
    expect(statuses[0].find('.sr-only').text()).toBe('Įkeltas');
    expect(statuses[1].find('.sr-only').text()).toBe('Neįkelta');
    expect(statuses.map(status => status.attributes('data-status-role'))).toEqual(['success', 'attention']);
    expect(statuses[0].find('svg').classes()).toContain('text-status-success');
    expect(statuses[1].find('svg').classes()).toContain('text-status-attention');
    expect(statuses[0].find('svg').classes()).toContain('size-3.5');
  });

  it('opens the selected incomplete item from the completion shortcuts', async () => {
    const wrapper = createWrapper({
      completion: {
        status: 'incomplete',
        missingActions: [
          { type: 'agenda_item_vote_missing', agenda_item_id: 'a2', title: 'Biudžetas', position: 2, missing_fields: ['decision'] },
          { type: 'agenda_item_type_missing', agenda_item_id: 'a5', title: 'Kita', position: 5 },
        ],
      },
    });

    const completion = wrapper.find('[data-slot="meeting-completion"]');
    expect(completion.findAll('li')).toHaveLength(2);
    expect(wrapper.text()).toContain('Papildyti');
    await completion.find('li button').trigger('click');

    expect(router.visit).toHaveBeenCalledWith('/mocked/agendaItems.edit?agendaItem=a2&mode=edit&focus=votes');
  });

  /**
   * Editing is the page's headline action, so it keeps its own labelled button;
   * attaching another institution is rare enough to live in the overflow menu.
   */
  it('labels the edit button and keeps attaching an institution in the menu', () => {
    const wrapper = createWrapper({
      meeting: { ...baseMeeting, institutions: [{ id: 'i1', name: 'VU SA MIF' }] },
      availableInstitutionsForAttach: [{ id: 'i2', name: 'VU SA CHGF' }],
    });

    const menu = wrapper.find('[data-testid="dropdown-menu-content"]');

    expect(wrapper.text()).toContain('Redaguoti posėdį');
    expect(menu.text()).toContain('Pridėti instituciją');
    expect(menu.text()).not.toContain('Redaguoti posėdį');
  });

  it('opens the tab named by the ?tab= URL parameter', () => {
    window.history.replaceState({}, '', '/?tab=files');

    const wrapper = createWrapper();

    expect(wrapper.find('[data-testid="files-panel"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="files-panel"]').element.closest('section')?.className).toContain('md:block');
    expect(wrapper.find('[data-testid="agenda-list"]').element.closest('section')?.className).toContain('md:hidden');
  });

  describe('tasks tab count', () => {
    const tasksTabText = (wrapper: ReturnType<typeof mount>) =>
      wrapper.findAll('[role="tab"], button')
        .map(node => node.text())
        .find(text => text.includes('Užduotys')) ?? '';

    it('counts only the tasks still outstanding', () => {
      const wrapper = createWrapper({
        meeting: { ...baseMeeting },
        tasks: [
          { id: 't1', completed_at: null },
          { id: 't2', completed_at: '2026-03-04T10:00:00.000Z' },
          { id: 't3', completed_at: null },
        ],
      });

      // Three tasks, one done — the badge reports what is left to act on.
      expect(tasksTabText(wrapper)).toContain('2');
    });

    it('shows no number once every task is done', () => {
      const wrapper = createWrapper({
        meeting: { ...baseMeeting },
        tasks: [{ id: 't1', completed_at: '2026-03-04T10:00:00.000Z' }],
      });

      expect(tasksTabText(wrapper)).not.toContain('1');
    });
  });

  describe('meeting navigation', () => {
    const recordNavigation = {
      position: 2,
      total: 3,
      previousHref: '/m/earlier',
      nextHref: '/m/later',
      previousLabel: '02-04',
      nextLabel: '04-04',
    };

    const mountWithNavigation = () => createWrapper({ recordNavigation });

    it('shows the meeting position and opens the neighbouring meetings', async () => {
      const wrapper = mountWithNavigation();

      expect(wrapper.text()).toContain('2 / 3');
      await wrapper.find('button[aria-label="Ankstesnis įrašas"]').trigger('click');
      expect(router.visit).toHaveBeenCalledWith('/m/earlier');
      await wrapper.find('button[aria-label="Kitas įrašas"]').trigger('click');
      expect(router.visit).toHaveBeenCalledWith('/m/later');
    });

    it('labels ‹ › with the neighbouring dates', () => {
      const wrapper = mountWithNavigation();

      expect(wrapper.find('button[aria-label="Ankstesnis įrašas"]').text()).toBe('02-04');
      expect(wrapper.find('button[aria-label="Kitas įrašas"]').text()).toBe('04-04');
    });
  });
});
