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
  heroImage: null,
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
  it('keeps tasks and destinations beside quick actions, then puts the other content below', () => {
    const wrapper = mountPage({
      upcomingMeetings: [{ id: '1', title: 'Meeting' }],
      coordinator: { name: 'Jonas', email: 'jonas@vusa.lt', profile_photo_path: null, duty: null },
    });
    const tasks = wrapper.find('attention-queue-stub');
    const quickAccess = wrapper.find('quick-access-stub');
    const primary = wrapper.find('[data-slot="home-primary-section"]');
    const secondary = wrapper.find('[data-slot="home-secondary-section"]');

    expect(tasks.element.nextElementSibling).toBe(quickAccess.element);
    expect(primary.find('aside').find('create-shortcuts-stub').exists()).toBe(true);
    expect(primary.element.nextElementSibling).toBe(secondary.element);
    expect(secondary.find('upcoming-meetings-list-stub').exists()).toBe(true);
    expect(secondary.find('recently-edited-list-stub').exists()).toBe(true);
    expect(wrapper.find('coordinator-card-stub').exists()).toBe(false);
  });

  it('shows the first three upcoming tasks and counts all remaining open tasks', () => {
    const upcomingTasks = Array.from({ length: 5 }, (_, index) => ({ id: String(index + 1) }));
    const queue = mountPage({ upcomingTasks, taskStats: { total: 7, overdue: 0, dueSoon: 5 } })
      .findComponent({ name: 'AttentionQueue' });

    expect(queue.props('tasks')).toEqual(upcomingTasks.slice(0, 3));
    expect(queue.props('remainingCount')).toBe(4);
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

  it('greets the rep with "Labas" in the hero and hands it the institution image', () => {
    const heroImage = { url: '/uploads/institution.jpg', focalPoint: '40% 30%' };
    const hero = mountPage({ heroImage }).findComponent({ name: 'HomeHero' });

    expect(hero.props('greeting')).toMatch(/^Labas(, .+)?$/);
    expect(hero.props('image')).toEqual(heroImage);
  });

  it('passes the registration forms the server allows on to quick access', () => {
    const registrationForms = [{ key: 'member', href: '/mano/forms/1' }];

    expect(mountPage({ registrationForms }).findComponent({ name: 'QuickAccess' }).props('registrationForms')).toEqual(registrationForms);
  });
});
