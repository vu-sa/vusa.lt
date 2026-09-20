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
  unreadNotificationsCount: 0,
  hasNotifications: false,
  taskStats: { total: 0, overdue: 0, dueSoon: 0 },
  upcomingTasks: [],
  upcomingMeetings: [],
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

  it('adds the year\'s recorded meetings as the lead once the count arrives, and only when there is one', () => {
    expect(mountPage().text()).not.toContain('home.impact');
    expect(mountPage({ recordedMeetingsThisYear: 0 }).text()).not.toContain('home.impact');
    expect(mountPage({ recordedMeetingsThisYear: 6 }).text()).toContain('home.impact');
  });
});
