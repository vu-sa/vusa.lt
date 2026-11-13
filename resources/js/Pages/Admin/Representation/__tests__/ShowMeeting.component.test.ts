import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { router, WhenVisible } from '@inertiajs/vue3';
import * as i18n from 'laravel-vue-i18n';

import ShowMeeting from '@/Pages/Admin/Representation/ShowMeeting.vue';
import { commonStubs } from '@/tests/stubs';
import { ActionType, AgendaItemType, MeetingKind, MeetingType } from '@/Types/enums';

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
    emits: ['add'],
    template: '<div data-testid="agenda-list"><button data-testid="paste-agenda" @click="$emit(\'add\', \'paste\')">Įklijuoti darbotvarkę</button></div>',
  },
  MeetingNavigationCards: { template: '<div />' },
  DiscussionPanel: { template: '<div />' },
  FileableFilesPanel: { name: 'FileableFilesPanel', template: '<div data-testid="files-panel" />' },
  TaskManager: { name: 'TaskManager', template: '<div data-testid="task-manager" />' },
  MeetingDocumentsPanel: { name: 'MeetingDocumentsPanel', template: '<div data-testid="documents-panel" />' },
  MeetingForm: { name: 'MeetingForm', props: ['open'], template: '<div />' },
  AddAgendaItemsSheet: {
    name: 'AddAgendaItemsSheet',
    props: { open: Boolean, initialMode: String, meetingId: String, recentAgendas: Array },
    template: '<div data-testid="add-agenda-sheet" :data-open="open" :data-mode="initialMode" />',
  },
  AnnounceMeetingDialog: { template: '<div />' },
  RecordActivity: { template: '<div data-testid="record-activity" />' },
};

type PageProps = InstanceType<typeof ShowMeeting>['$props'];
type Meeting = PageProps['meeting'];
type FullAgendaItem = Extract<Meeting['agenda_items'][number], { votes: unknown[] }>;
type Institution = Meeting['institutions'][number];
type Representative = PageProps['representatives'][number];
type Task = NonNullable<PageProps['tasks']>[number];

const makeAgendaItem = (overrides: Partial<FullAgendaItem> = {}): FullAgendaItem => ({
  id: 'a1',
  order: 1,
  title: 'Studijų klausimai',
  type: AgendaItemType.Informational,
  is_private: false,
  brought_by_students: false,
  start_time: null,
  end_time: null,
  votes: [],
  comments_count: 0,
  has_notes: false,
  can: { update: true, delete: true },
  ...overrides,
});

const makeInstitution = (overrides: Partial<Institution> = {}): Institution => ({
  id: 'i1',
  name: 'VU SA MIF',
  tenant_id: null,
  is_active: true,
  tenant: null,
  ...overrides,
});

const makeRepresentative = (overrides: Partial<Representative> = {}): Representative => ({
  id: 'u1',
  name: 'Jonas Jonaitis',
  email: 'jonas@example.com',
  phone: null,
  facebook_url: null,
  pronouns: null,
  show_pronouns: false,
  profile_photo_path: null,
  profile_photo_focal_point: null,
  ...overrides,
});

const makeTask = (overrides: Partial<Task> = {}): Task => ({
  id: 't1',
  name: 'Papildyk posėdžio darbotvarkę',
  description: null,
  due_date: null,
  completed_at: null,
  cancelled_at: null,
  created_at: '2026-03-01T09:00:00.000Z',
  updated_at: '2026-03-01T09:00:00.000Z',
  action_type: ActionType.Manual,
  metadata: null,
  progress: null,
  is_overdue: false,
  can_be_manually_completed: true,
  can_update: true,
  icon: 'clipboard-check',
  color: 'zinc',
  taskable: { id: 'meet1', name: 'Senato posėdis', type: 'meeting' },
  taskable_type: 'meeting',
  taskable_id: 'meet1',
  users: [],
  ...overrides,
});

const baseMeeting: Meeting = {
  id: 'meet1',
  title: 'Senato posėdis',
  start_time: '2026-03-04T10:00:00.000Z',
  end_time: null,
  created_at: '2026-03-01T09:00:00.000Z',
  updated_at: '2026-03-01T09:00:00.000Z',
  type: MeetingType.InPerson,
  meeting_kind: null,
  inherited_meeting_kind: MeetingKind.Sitting,
  title_noun: 'posėdis',
  description: { lt: '', en: '' },
  institutions: [],
  agenda_items: [makeAgendaItem(), makeAgendaItem({ id: 'a2', order: 2 })],
  sharepointPath: null,
  sharepointFolderUrl: null,
  has_calendar_event: false,
  type_label: 'Gyvas posėdis',
  has_protocol: false,
};

const createWrapper = (overrides: Partial<PageProps> = {}) => {
  const props: PageProps = {
    meeting: baseMeeting,
    representatives: [],
    incompleteTasksCount: 0,
    files: [],
    availableInstitutionsForAttach: [],
    readOnly: false,
    publicVisibility: { mode: 'internal', url: null },
    abilities: {
      update: true,
      delete: true,
      discard: false,
      createAgendaItems: true,
      reorderAgendaItems: true,
      attachInstitution: true,
    },
    completion: { status: 'complete', missingActions: [] },
    ...overrides,
  };

  return mount(ShowMeeting, { props, global: { stubs } });
};

describe('ShowMeeting.vue', () => {
  beforeEach(() => {
    vi.mocked(router.reload).mockClear();
    localStorage.clear();
    window.history.replaceState({}, '', '/');
  });

  it.each([
    ['lt', 'Senatas', 'Senato posėdis'],
    ['en', 'Senate', 'Senate meeting'],
  ])('includes the institution in the heading in %s', (locale, name, ending) => {
    const language = vi.spyOn(i18n, 'getActiveLanguage').mockReturnValue(locale);
    const wrapper = createWrapper({
      meeting: {
        ...baseMeeting,
        title_noun: locale === 'lt' ? 'posėdis' : 'meeting',
        institutions: [makeInstitution({ id: 'i1', name })],
      },
    });

    try {
      expect(wrapper.get('[data-slot="record-title"]').text()).toMatch(new RegExp(`${ending}$`));
      if (locale === 'en') expect(wrapper.get('[data-slot="record-title"]').text()).toContain('March');
    }
    finally {
      wrapper.unmount();
      language.mockRestore();
    }
  });

  it('uses the server’s joint noun without an institution prefix', () => {
    const wrapper = createWrapper({
      meeting: {
        ...baseMeeting,
        title_noun: 'jungtinė konferencija',
        institutions: [makeInstitution({ id: 'i1', name: 'Senatas' }), makeInstitution({ id: 'i2', name: 'Taryba' })],
      },
    });

    const title = wrapper.get('[data-slot="record-title"]').text();
    expect(title).toContain('jungtinė konferencija');
    expect(title).not.toContain('Senato');
    expect(title).not.toContain('Tarybos');
  });

  it('shows only representatives in the people fact', () => {
    const wrapper = createWrapper({
      representatives: [makeRepresentative()],
    });

    const peopleFact = wrapper.findAll('dl > div')[4];

    expect(peopleFact.find('dt').text()).toBe('Atstovai');
    expect(peopleFact.find('[data-slot="users-fact-list"]').text()).toContain('Jonas Jonaitis');
    expect(peopleFact.find('[data-testid="users-avatar-group"]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('secretaries.label');
  });

  it('keeps the expandable avatar group for multiple representatives', () => {
    const peopleFact = createWrapper({
      representatives: [makeRepresentative(), makeRepresentative({ id: 'u2', name: 'Rūta Petraitė' })],
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
    const menuText = (props: Partial<PageProps> = {}) =>
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
      const announced = { ...baseMeeting, has_calendar_event: true };

      expect(menuText({ meeting: announced })).toContain('Atsieti nuo kalendoriaus');
      expect(menuText({ meeting: announced, governanceScope: 'vusa' }))
        .toContain('Atsieti nuo kalendoriaus');
    });

    it('unlinks without reloading the document and task panels', async () => {
      const wrapper = createWrapper({ meeting: { ...baseMeeting, has_calendar_event: true } });

      wrapper.findComponent({ name: 'RecordPage' }).vm.$emit('action', 'unlink-calendar');
      await wrapper.vm.$nextTick();

      expect(router.delete).toHaveBeenCalledWith(
        '/mocked/meetings.calendarEvent.destroy?meeting=meet1',
        expect.objectContaining({ except: ['documents', 'tasks', 'pendingDocuments'] }),
      );
    });
  });

  it.each([
    ['files', 'files'],
    ['tasks', 'tasks'],
    ['documents', ['documents', 'pendingDocuments']],
  ] as const)('loads the %s section through WhenVisible without selecting its tab', (section, data) => {
    const wrapper = createWrapper({ files: undefined, governanceScope: 'vusa' });
    const panel = wrapper.findAllComponents(WhenVisible)
      .find(component => component.element.closest('section')?.getAttribute('aria-labelledby')?.endsWith(`-${section}`));

    expect(panel).toBeDefined();
    expect(panel?.props('data')).toEqual(data);
    expect(panel?.props('always')).toBe(false);
    expect(panel?.props('buffer')).toBe(0);
    expect(panel?.vm.$slots.fallback).toBeTypeOf('function');
    expect(router.reload).not.toHaveBeenCalled();
    wrapper.unmount();
  });

  it.each([
    ['files', 'files'],
    ['tasks', 'tasks'],
    ['documents', ['documents', 'pendingDocuments']],
  ] as const)('keeps the %s deep link ready for visibility loading', (tab, data) => {
    window.history.replaceState({}, '', `/?tab=${tab}`);
    const wrapper = createWrapper({ files: undefined, governanceScope: 'vusa' });
    const record = wrapper.findComponent({ name: 'RecordPage' });
    const panel = wrapper.findAllComponents(WhenVisible)
      .find(component => component.element.closest('section')?.getAttribute('aria-labelledby')?.endsWith(`-${tab}`));

    expect(record.props('section')).toBe(tab);
    expect(panel?.props('data')).toEqual(data);
    expect(panel?.element.closest('section')?.className).toContain('md:block');
    expect(router.reload).not.toHaveBeenCalled();
    wrapper.unmount();
  });

  it('keeps internal panels out of the read-only agenda view', () => {
    window.history.replaceState({}, '', '/?tab=files');
    const wrapper = createWrapper({ readOnly: true, files: undefined, governanceScope: 'vusa' });

    expect(wrapper.findComponent({ name: 'RecordPage' }).props('section')).toBe('agenda');
    expect(wrapper.findAllComponents(WhenVisible)).toHaveLength(0);
    expect(wrapper.find('[data-testid="agenda-list"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="files-panel"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="task-manager"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="documents-panel"]').exists()).toBe(false);
    expect(router.reload).not.toHaveBeenCalled();
    wrapper.unmount();
  });

  it('keeps unopened panels out of mutation responses and refreshes loaded tasks', () => {
    const wrapper = createWrapper({ files: undefined, tasks: [] });
    const before = [...vi.mocked(router.on).mock.calls].reverse().find(([event]) => event === 'before')?.[1];
    expect(before).toBeDefined();
    const event = new CustomEvent('inertia:before', {
      detail: { visit: { method: 'patch', except: [] } },
    });
    before?.(event as Parameters<NonNullable<typeof before>>[0]);
    expect(event.detail.visit.except).toEqual(['files', 'documents', 'pendingDocuments', 'recentAgendas']);
    wrapper.unmount();
  });

  it('shows the meeting type supplied by the server', () => {
    const wrapper = createWrapper();
    expect(wrapper.findComponent({ name: 'RecordPage' }).props('eyebrowSuffix')).toBe('Gyvas posėdis');
    wrapper.unmount();
  });

  it('loads attach options when the dialog opens', async () => {
    const wrapper = createWrapper({ availableInstitutionsForAttach: undefined });
    expect(router.reload).not.toHaveBeenCalled();
    wrapper.findComponent({ name: 'RecordPage' }).vm.$emit('action', 'attach-institution');
    await wrapper.vm.$nextTick();
    expect(router.reload).toHaveBeenCalledExactlyOnceWith(expect.objectContaining({ only: ['availableInstitutionsForAttach'] }));
    wrapper.unmount();
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

  it('opens the paste mode when the agenda paste action is clicked', async () => {
    const wrapper = createWrapper({ meeting: { ...baseMeeting, agenda_items: [] } });

    await wrapper.find('[data-testid="paste-agenda"]').trigger('click');

    const sheet = wrapper.find('[data-testid="add-agenda-sheet"]');
    expect(sheet.attributes('data-open')).toBe('true');
    expect(sheet.attributes('data-mode')).toBe('paste');
  });

  it('opens the agenda sheet when Papildyti points to a missing agenda', async () => {
    const wrapper = createWrapper({
      meeting: { ...baseMeeting, agenda_items: [] },
      completion: { status: 'incomplete', missingActions: [{ type: 'agenda_missing' }] },
    });

    await wrapper.findAll('button').find(button => button.text() === 'Papildyti')!.trigger('click');

    const sheet = wrapper.find('[data-testid="add-agenda-sheet"]');
    expect(sheet.attributes('data-open')).toBe('true');
    expect(sheet.attributes('data-mode')).toBe('paste');
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
      meeting: { ...baseMeeting, institutions: [makeInstitution({ id: 'i1', name: 'VU SA MIF' })] },
    });

    const link = wrapper.findAll('a').find(anchor => anchor.text() === 'VU SA MIF');

    expect(link?.attributes('href')).toContain('institutions.show');
    expect(wrapper.find('[data-slot="record-title"]').text()).not.toContain('10:00');
  });

  it('shows a far meeting as its date and Vilnius clock time after visibility', () => {
    const facts = createWrapper().findAll('dl > div');

    expect(facts[2].find('dt').text()).toBe('Laikas');
    expect(facts[2].find('dd').text()).toBe('2026-03-04 · 12:00');
  });

  it('shows a near meeting relative to now', () => {
    const startTime = new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString();
    const facts = createWrapper({ meeting: { ...baseMeeting, start_time: startTime } }).findAll('dl > div');

    expect(facts[2].find('dd').text()).toMatch(/^prieš 2 d\. · \d{2}:\d{2}$/);
  });

  it('shows visibility next to status with a globe and a surface matching public access', () => {
    const publicFact = createWrapper({ publicVisibility: { mode: 'public_page', url: 'https://www.vusa.test/lt/meeting' } }).findAll('dl > div')[1];
    const privateFact = createWrapper().findAll('dl > div')[1];

    expect(publicFact.classes()).toContain('bg-status-success-surface');
    expect(publicFact.find('dt').attributes('aria-label')).toBe('Matomumas');
    expect(publicFact.find('dt svg').exists()).toBe(true);
    expect(publicFact.find('dt').text()).toBe('');
    expect(publicFact.find('dd a').text()).toBe('meetings.record.visible_public');
    expect(privateFact.classes()).toContain('bg-status-neutral-surface');
    expect(privateFact.find('dd').text()).toContain('meetings.record.internal_only');
  });

  it('shows a meeting announced through a published calendar event as visible there', () => {
    const fact = createWrapper({ publicVisibility: { mode: 'calendar_event', url: 'https://www.vusa.test/lt/kalendorius/2026/posedis' } }).findAll('dl > div')[1];

    expect(fact.classes()).toContain('bg-status-success-surface');
    expect(fact.find('dd a').text()).toBe('meetings.record.visible_event');
    expect(fact.find('dd a').attributes('href')).toBe('https://www.vusa.test/lt/kalendorius/2026/posedis');
  });

  it('keeps a meeting with a draft calendar event internal and says why', () => {
    const fact = createWrapper({ publicVisibility: { mode: 'calendar_draft', url: null } }).findAll('dl > div')[1];

    expect(fact.classes()).toContain('bg-status-neutral-surface');
    expect(fact.find('dd a').exists()).toBe(false);
    expect(fact.find('dd').text()).toContain('meetings.record.event_draft');
  });

  it('explains what vusa.lt shows for this meeting', async () => {
    const wrapper = createWrapper({ publicVisibility: { mode: 'public_page', url: 'https://www.vusa.test/lt/meeting' } });
    const dialog = wrapper.findComponent({ name: 'MeetingPublicVisibilityDialog' });

    expect(dialog.props('open')).toBe(false);

    await wrapper.get('[data-testid="meeting-visibility-explain"]').trigger('click');

    expect(dialog.props('open')).toBe(true);
    expect(dialog.props('mode')).toBe('public_page');
  });

  it('hides the explanation trigger in the read-only view', () => {
    expect(createWrapper({ readOnly: true }).find('[data-testid="meeting-visibility-explain"]').exists()).toBe(false);
  });

  it('states only the protocol as expected after the meeting', () => {
    const fact = createWrapper({ meeting: { ...baseMeeting, has_protocol: true } }).findAll('dl > div')[5];
    const statuses = fact.findAll('dd [data-status-role]');

    expect(fact.find('dt').text()).toBe('meetings.record.after_meeting');
    expect(fact.find('dd > div').classes()).toContain('text-xs');
    expect(statuses[0].findAll('span')[0].text()).toBe('meetings.record.protocol');
    expect(statuses[0].find('.sr-only').text()).toBe('Įkeltas');
    expect(statuses.map(status => status.attributes('data-status-role'))).toEqual(['success']);
    expect(statuses[0].find('svg').classes()).toContain('text-status-success');
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
      meeting: { ...baseMeeting, institutions: [makeInstitution({ id: 'i1', name: 'VU SA MIF' })] },
      availableInstitutionsForAttach: [{ id: 'i2', name: 'VU SA CHGF', is_active: true, tenant_shortname: null }],
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
          makeTask({ id: 't1', completed_at: null }),
          makeTask({ id: 't2', completed_at: '2026-03-04T10:00:00.000Z' }),
          makeTask({ id: 't3', completed_at: null }),
        ],
      });

      // Three tasks, one done — the badge reports what is left to act on.
      expect(tasksTabText(wrapper)).toContain('2');
    });

    it('shows no number once every task is done', () => {
      const wrapper = createWrapper({
        meeting: { ...baseMeeting },
        tasks: [makeTask({ id: 't1', completed_at: '2026-03-04T10:00:00.000Z' })],
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

it.each([true, false])('opens the meeting edit sheet from action=edit only for editors (%s)', async (allowed) => {
  window.history.replaceState({}, '', '/mano/meetings/meet1?action=edit&tab=agenda');
  const wrapper = createWrapper({ abilities: { update: allowed, delete: false, discard: false, createAgendaItems: false, reorderAgendaItems: false, attachInstitution: false } });
  await wrapper.vm.$nextTick();
  expect(wrapper.findComponent({ name: 'MeetingForm' }).props('open')).toBe(allowed);
  if (allowed) expect(new URLSearchParams(window.location.search).has('action')).toBe(false);
  expect(new URLSearchParams(window.location.search).get('tab')).toBe('agenda');
  wrapper.unmount();
  window.history.replaceState({}, '', '/');
});
