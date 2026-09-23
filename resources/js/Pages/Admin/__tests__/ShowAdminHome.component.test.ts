import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ShowAdminHome from '@/Pages/Admin/ShowAdminHome.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const open = vi.fn();
vi.mock('@/Composables/useActionWindow', () => ({
  useActionWindow: () => ({ open, isOpen: { value: false } }),
}));

const startTour = vi.fn();
const startTourIfNew = vi.fn();
vi.mock('@/Composables/useProductTour', () => ({
  useProductTour: () => ({ startTour, startTourIfNew }),
}));
vi.mock('@/Composables/useTourProvider', () => ({ provideTour: vi.fn() }));
vi.mock('@/Composables/useApi', () => ({
  useApiMutation: vi.fn(() => ({ execute: vi.fn().mockResolvedValue(undefined) })),
}));

// The bands are tested on their own; here only what the page decides matters.
const stubs = {
  AttentionQueue: true,
  CreateShortcuts: true,
  QuickAccess: true,
  UpcomingMeetingsList: true,
  InstitutionsNeedingAttention: true,
  CoordinatorCard: true,
  RecentlyEditedList: true,
  SiteContentLists: true,
  FirstLoginChecklist: { name: 'FirstLoginChecklist', template: '<div data-stub="checklist" />' },
  AccessChangeBand: { name: 'AccessChangeBand', template: '<div data-stub="band" />' },
};

const baseProps = {
  onboardingChecklist: null,
  accessChanges: [],
  actionWindowLaunch: null,
  taskStats: { total: 0, overdue: 0, dueSoon: 0 },
  upcomingTasks: [],
  upcomingMeetings: [],
  heroNews: null,
  registrationForms: [],
};

const mountPage = (props: Record<string, unknown> = {}) => mount(ShowAdminHome, {
  props: { ...baseProps, ...props } as never,
  global: { stubs },
});

beforeEach(() => {
  vi.clearAllMocks();
  vi.useFakeTimers();
  vi.mocked(usePage).mockReturnValue(createMockPage() as ReturnType<typeof usePage>);
  window.history.replaceState({}, '', '/mano');
});

describe('ShowAdminHome', () => {
  it('puts destinations right under the task preview, and create actions after them', () => {
    const wrapper = mountPage();
    const tasks = wrapper.find('attention-queue-stub');
    const quickAccess = wrapper.find('quick-access-stub');
    const shortcuts = wrapper.find('create-shortcuts-stub');

    expect(tasks.element.nextElementSibling).toBe(quickAccess.element);
    expect(quickAccess.element.compareDocumentPosition(shortcuts.element) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
  });

  it('sums up waiting and overdue tasks under the greeting, and says nothing when none wait', () => {
    expect(mountPage().find('[data-testid="hero-summary"]').exists()).toBe(false);

    const summary = mountPage({ taskStats: { total: 3, overdue: 1, dueSoon: 0 } }).find('[data-testid="hero-summary"]');
    expect(summary.text()).toBe('home.summary.waiting · home.summary.overdue');
    expect(mountPage({ taskStats: { total: 3, overdue: 0, dueSoon: 0 } }).find('[data-testid="hero-summary"]').text())
      .toBe('home.summary.waiting');
  });

  it('shows the checklist and the access band only when the server sends them', () => {
    expect(mountPage().find('[data-stub="checklist"]').exists()).toBe(false);
    expect(mountPage().find('[data-stub="band"]').exists()).toBe(false);

    const wrapper = mountPage({
      onboardingChecklist: { items: [], doneCount: 0 },
      accessChanges: [{ kind: 'started', dutyName: 'X', institutionName: null, date: '2026-09-20', effectiveOn: '2026-09-20', isExOfficio: false }],
    });

    expect(wrapper.find('[data-stub="checklist"]').exists()).toBe(true);
    expect(wrapper.find('[data-stub="band"]').exists()).toBe(true);
  });

  it('no longer starts the welcome tour by itself — the checklist replaces it', () => {
    mountPage();
    vi.advanceTimersByTime(5000);

    expect(startTourIfNew).not.toHaveBeenCalled();
  });

  it('opens the window on the requested flow with the institution filled in, then cleans the URL', () => {
    window.history.replaceState({}, '', '/mano?window=check-in&institution=abc');

    mountPage({ actionWindowLaunch: { flow: 'check-in', institution: { id: 'abc', name: 'VU MIF', isInternal: true } } });

    expect(open).toHaveBeenCalledWith({ flow: 'check-in', institution: { id: 'abc', name: 'VU MIF', isInternal: true } });
    expect(window.location.search).toBe('');
  });

  it('opens nothing when the URL asked for nothing it may honour', () => {
    mountPage({ actionWindowLaunch: null });

    expect(open).not.toHaveBeenCalled();
  });

  it('greets the rep with "Labas" in the hero and hands it the newest news', () => {
    const heroNews = { id: 1, title: 'Naujiena', image: '/uploads/n.jpg', publish_time: '2026-09-21T10:00:00Z', public_url: null, archive_url: '/lt/naujienos' };
    const hero = mountPage({ heroNews }).findComponent({ name: 'HomeHero' });

    expect(hero.props('greeting')).toMatch(/^Labas(, .+)?$/);
    expect(hero.props('news')).toEqual(heroNews);
  });

  it('passes the registration forms the server allows on to quick access', () => {
    const registrationForms = [{ key: 'member', href: '/mano/forms/1' }];

    expect(mountPage({ registrationForms }).findComponent({ name: 'QuickAccess' }).props('registrationForms')).toEqual(registrationForms);
  });
});
