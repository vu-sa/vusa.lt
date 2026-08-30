import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

import ShowUser from '@/Pages/Admin/People/ShowUser.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

const stubs = {
  ActivityLogSheet: { template: '<div data-testid="activity-log" />' },
  MoreOptionsButton: {
    name: 'MoreOptionsButton',
    props: ['edit', 'delete', 'small', 'disabled', 'moreOptions'],
    template: '<div data-testid="more-options" />',
  },
  TaskManager: { template: '<div data-testid="task-manager" />' },
  DutiableTimelineDialog: { template: '<div />' },
  UsersAvatarGroup: { props: ['users', 'max', 'size', 'clickable'], template: '<div class="avatar-group" />' },
};

const makeDuty = (overrides: Record<string, unknown> = {}) => ({
  id: 'd1',
  name: 'Koordinatorius',
  places_to_occupy: 1,
  institution: { id: 'i1', name: 'VU SA Filosofijos fakultete', tenant: { shortname: 'VU SA FsF' } },
  current_users: [{ id: 'u1', name: 'Justina Preidytė' }],
  pivot: { start_date: '2024-07-01', end_date: null },
  ...overrides,
});

const baseUser = {
  id: 'u1',
  name: 'Justina Preidytė',
  email: 'justina@example.com',
  phone: '+370 612 77 522',
  pronouns: 'ji/jos',
  show_pronouns: true,
  has_password: true,
  roles: [],
  current_duties: [makeDuty()],
  previous_duties: [makeDuty({ id: 'd2', name: 'Kuratorius', pivot: { start_date: '2022-09-01', end_date: '2023-06-01' } })],
};

const createWrapper = (overrides: Record<string, unknown> = {}, can: Record<string, boolean> = { update: true, delete: true }) =>
  mount(ShowUser, {
    props: {
      user: { ...baseUser, ...overrides } as never,
      tasks: [],
      taskStats: { total: 10, completed: 0, pending: 10, overdue: 0, autoCompleting: 0 },
      can: can as never,
    },
    global: { stubs },
  });

describe('ShowUser.vue', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('counts duties in the tab from both current and previous assignments', () => {
    const wrapper = createWrapper({
      current_duties: [makeDuty(), makeDuty({ id: 'd3' })],
      previous_duties: [makeDuty({ id: 'd4' })],
    });

    const dutiesTab = wrapper.findAll('[role="tab"]').find(tab => tab.text().includes('Pareigos'));
    expect(dutiesTab?.text()).toContain('3');
  });

  it('takes the tasks tab count from the task stats', () => {
    const tasksTab = createWrapper().findAll('[role="tab"]').find(tab => tab.text().includes('Užduotys'));
    expect(tasksTab?.text()).toContain('10');
  });

  it('labels each duty section with its own count so the tab total is not contradicted', () => {
    const wrapper = createWrapper();
    const sections = wrapper.findAll('[data-slot="duty-section-list"]');

    expect(sections).toHaveLength(2);
    expect(sections[0].text()).toContain('Dabartinės pareigos');
    expect(sections[0].text()).toContain('(1)');
    expect(sections[1].text()).toContain('Buvusios pareigos');
    expect(sections[1].text()).toContain('(1)');
  });

  it('shows previous duties dimmed so active ones stay dominant', () => {
    const wrapper = createWrapper();
    const pastCard = wrapper.findAll('[data-slot="duty-section-list"]')[1].find('[data-slot="duty-summary-card"]');

    expect(pastCard.classes()).toContain('opacity-70');
  });

  it('omits duty staffing from the overview, where it says nothing about the member', () => {
    const wrapper = createWrapper();
    const overview = wrapper.findAll('[data-slot="duty-section-list"]')[0];

    expect(overview.text()).not.toContain('užimta');
    expect(overview.find('.avatar-group').exists()).toBe(false);
  });

  it('uses the primary current duty as the subtitle, inflected for the holder', () => {
    // The page locale defaults to 'lt' in the test setup.
    const subtitle = createWrapper().find('[data-slot="show-page-hero-subtitle"]');

    expect(subtitle.text()).toContain('Koordinatorė');
    expect(subtitle.text()).not.toContain('justina@example.com');
  });

  it('lists contact details in a plain sidebar section rather than a card', () => {
    const wrapper = createWrapper();

    expect(wrapper.find('a[href="mailto:justina@example.com"]').exists()).toBe(true);
    expect(wrapper.find('a[href="tel:+370 612 77 522"]').exists()).toBe(true);
    // Duty cards are the page's only card surface.
    expect(wrapper.findAll('[data-slot="card"]')).toHaveLength(0);
  });

  it('links the duty name only, so the institution line carries no link styling', () => {
    const card = createWrapper().find('[data-slot="duty-summary-card"]');

    expect(card.text()).toContain('VU SA Filosofijos fakultete');
    expect(card.findAll('a')).toHaveLength(1);
    expect(card.find('a').text()).toContain('Koordinatorė');
  });

  it('renders the profile photo when there is one, and initials otherwise', async () => {
    expect(createWrapper().text()).toContain('JP');

    const withPhoto = createWrapper({ profile_photo_path: '/photo.jpg', profile_photo_focal_point: '50% 20%' });
    await withPhoto.vm.$nextTick();
    const img = withPhoto.find('img');

    expect(img.attributes('src')).toBe('/photo.jpg');
    expect(img.attributes('style')).toContain('object-position: 50% 20%');
  });

  it('offers edit actions only to a user who may update the record', () => {
    const editable = createWrapper();
    expect(editable.text()).toContain('Redaguoti');
    expect(editable.findComponent({ name: 'MoreOptionsButton' }).exists()).toBe(true);

    const readOnly = createWrapper({}, { update: false, delete: false });
    expect(readOnly.text()).not.toContain('Redaguoti');
    expect(readOnly.findComponent({ name: 'MoreOptionsButton' }).exists()).toBe(false);
  });

  it('keeps the delete entry out of the menu without the delete permission', () => {
    const wrapper = createWrapper({}, { update: true, delete: false });
    const menu = wrapper.findComponent({ name: 'MoreOptionsButton' });

    expect(menu.props('edit')).toBe(true);
    expect(menu.props('delete')).toBe(false);
  });

  it('falls back to an empty state when the member holds no duties', () => {
    const wrapper = createWrapper({ current_duties: [], previous_duties: [] });

    expect(wrapper.findAll('[data-slot="duty-section-list"]')).toHaveLength(0);
    expect(wrapper.text()).toContain('Pareigų nėra');
  });
});
