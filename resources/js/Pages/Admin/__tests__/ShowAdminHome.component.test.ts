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
  MyActivityRequests: { name: 'MyActivityRequests', props: ['requests'], template: '<div v-if="requests.total" data-testid="active-requests" />' },
  AttentionQueue: true,
  CreateShortcuts: true,
  QuickAccess: true,
  UpcomingMeetingsList: true,
  InstitutionsNeedingAttention: true,
  RecentlyEditedList: true,
  SiteContentLists: true,
  ReservationDraftSummary: { name: 'ReservationDraftSummary', props: ['draft'], template: '<div data-stub="reservation-draft">{{ draft.count }}</div>' },
};

const baseProps = {
  activeActivityRequests: { total: 0, data: [] },
  upcomingMeetingsTotal: 0,
  actionWindowLaunch: null,
  taskStats: { total: 0, overdue: 0, dueSoon: 0 },
  upcomingTasks: [],
  upcomingMeetings: [],
  heroImage: null,
  registrationForms: [],
  reservationDraft: null,
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
  it('keeps own pending requests in the primary section even without representation access', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: { create: { meeting: false } } } }) as ReturnType<typeof usePage>);
    const wrapper = mountPage({ activeActivityRequests: { total: 1, data: [] } });
    const primary = wrapper.get('[data-slot="home-primary-section"]');
    expect(primary.element.firstElementChild?.querySelector('[data-testid="active-requests"]')).not.toBeNull();
  });

  it('keeps the work in one main column and quick actions at the top of one sidebar', () => {
    const wrapper = mountPage({
      upcomingMeetings: [{ id: '1', title: 'Meeting' }],
      upcomingTasks: [{ id: 'task-1' }],
      taskStats: { total: 1, overdue: 0, dueSoon: 0 },
    });
    const primary = wrapper.get('[data-slot="home-primary-section"]');
    const main = primary.element.firstElementChild as HTMLElement;
    const sidebar = primary.get('[data-slot="home-sidebar"]');

    expect(primary.classes()).toContain('lg:grid-cols-[1.4fr_1fr]');
    expect(main.firstElementChild).toBe(wrapper.get('attention-queue-stub').element);
    for (const section of ['institutions-needing-attention-stub', 'upcoming-meetings-list-stub', 'site-content-lists-stub', 'quick-access-stub']) {
      expect(main.querySelector(section)).not.toBeNull();
    }
    expect(sidebar.element.firstElementChild).toBe(sidebar.get('create-shortcuts-stub').element);
    expect(sidebar.find('recently-edited-list-stub').exists()).toBe(true);
    expect(wrapper.find('[data-slot="home-secondary-section"]').exists()).toBe(false);
  });

  it('lets institutions and quick actions share the full row when tasks collapse', () => {
    const primary = mountPage().find('[data-slot="home-primary-section"]');

    expect(primary.classes()).toContain('lg:grid-cols-[1.4fr_1fr]');
    expect(primary.element.firstElementChild?.querySelector('institutions-needing-attention-stub')).not.toBeNull();
    expect(primary.find('aside').find('create-shortcuts-stub').exists()).toBe(true);
  });

  it('keeps tasks beside quick actions without a gap when institutions are unavailable', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: { can: { create: { meeting: false }, index: { meeting: false } } },
    }) as ReturnType<typeof usePage>);

    const primary = mountPage({ upcomingTasks: [{ id: 'task-1' }], taskStats: { total: 1, overdue: 0, dueSoon: 0 } })
      .find('[data-slot="home-primary-section"]');

    expect(primary.classes()).toContain('lg:grid-cols-[1.4fr_1fr]');
    expect(primary.element.firstElementChild?.querySelector('attention-queue-stub')).not.toBeNull();
    expect(primary.find('institutions-needing-attention-stub').exists()).toBe(false);
    expect(primary.find('aside').find('create-shortcuts-stub').exists()).toBe(true);
  });

  it('keeps the two-column destination tiles in the main column whatever else it holds', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: { can: { create: { meeting: false }, index: { meeting: false } } },
    }) as ReturnType<typeof usePage>);

    const wrapper = mountPage({ reservationDraft: { name: null, count: 2, start_time: null, end_time: null } });
    const main = wrapper.get('[data-slot="home-primary-section"]').element.firstElementChild;

    expect(main?.contains(wrapper.get('[data-slot="home-destinations-section"]').element)).toBe(true);
    expect(wrapper.findAll('quick-access-stub')).toHaveLength(1);
    expect(wrapper.findComponent({ name: 'QuickAccess' }).props('columns')).toBe(2);
  });

  it('shows the first three upcoming tasks and counts all remaining open tasks', () => {
    const upcomingTasks = Array.from({ length: 5 }, (_, index) => ({ id: String(index + 1) }));
    const queue = mountPage({ upcomingTasks, taskStats: { total: 7, overdue: 0, dueSoon: 5 } })
      .findComponent({ name: 'AttentionQueue' });

    expect(queue.props('tasks')).toEqual(upcomingTasks.slice(0, 3));
    expect(queue.props('remainingCount')).toBe(4);
  });

  it('shows an unfinished reservation as its own section under the tasks, only when there is one', () => {
    expect(mountPage().find('[data-stub="reservation-draft"]').exists()).toBe(false);

    const wrapper = mountPage({ reservationDraft: { name: null, count: 2, start_time: null, end_time: null } });
    const column = wrapper.find('[data-slot="home-primary-section"]').element.firstElementChild!;
    const draft = wrapper.find('[data-stub="reservation-draft"]');

    expect(draft.text()).toBe('2');
    expect(draft.element.parentElement).toBe(column);
    expect(wrapper.find('attention-queue-stub [data-stub="reservation-draft"]').exists()).toBe(false);
  });

  it('starts the welcome tour for someone who has not seen it', () => {
    mountPage();
    vi.advanceTimersByTime(5000);

    expect(startTourIfNew).toHaveBeenCalledOnce();
  });

  it('leaves the tour for later when the page opens straight into the ActionWindow', () => {
    mountPage({ actionWindowLaunch: { flow: 'check-in', institution: { id: 'abc', name: 'VU MIF', isInternal: true } } });
    vi.advanceTimersByTime(5000);

    expect(startTourIfNew).not.toHaveBeenCalled();
  });

  it('opens the window on the requested flow with the institution filled in, then cleans the URL', () => {
    window.history.replaceState({}, '', '/mano?window=check-in&institution=abc');

    mountPage({ actionWindowLaunch: { flow: 'check-in', institution: { id: 'abc', name: 'VU MIF', isInternal: true } } });

    expect(open).toHaveBeenCalledWith({ flow: 'check-in', institution: { id: 'abc', name: 'VU MIF', isInternal: true } });
    expect(window.location.search).toBe('');
  });

  it('asks whether there was a meeting when recording activity for a lagging institution', () => {
    const wrapper = mountPage();

    wrapper.findComponent({ name: 'InstitutionsNeedingAttention' }).vm.$emit('record', { id: 'abc', name: 'VU MIF' });

    expect(open).toHaveBeenCalledWith({ flow: 'institution.report', institution: { id: 'abc', name: 'VU MIF' } });
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
