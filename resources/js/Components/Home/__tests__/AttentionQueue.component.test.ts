import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import AttentionQueue from '../AttentionQueue.vue';
import type { HomeTask } from '../types';

import { OVERVIEW_STATUS_KEY } from '@/Components/Patterns/overviewStatus';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage() as ReturnType<typeof usePage>);
});

const day = 24 * 60 * 60 * 1000;

const task = (overrides: Partial<HomeTask> = {}): HomeTask => ({
  id: '1',
  name: 'Užpildyti darbotvarkę',
  due_date: new Date(Date.now() + 20 * day).toISOString(),
  is_overdue: false,
  taskable_type: 'meeting',
  taskable_id: 'm1',
  taskable: { id: 'm1', name: 'Senato posėdis' },
  action_type: null,
  ...overrides,
});

const stats = { total: 1, overdue: 0, dueSoon: 0 };

const mountQueue = (tasks: HomeTask[], overrides: Partial<typeof stats> = {}) =>
  mount(AttentionQueue, { props: { tasks, stats: { ...stats, ...overrides }, moreHref: '/mano/tasks' } });

vi.mock('@/Composables/useTaskPresentation', async (importOriginal) => {
  const actual = await importOriginal<Record<string, unknown>>();

  return {
    ...actual,
    getTaskableUrl: () => '/mano/meetings/m1',
    getMeetingAgendaUrl: () => '/mano/meetings/m1?tab=agenda',
  };
});

describe('AttentionQueue', () => {
  it('shows simple task rows beneath a quiet heading', () => {
    const wrapper = mountQueue([task()]);

    expect(wrapper.find('.bg-foreground').exists()).toBe(false);
    expect(wrapper.find('h2').text()).toBe('home.tasks_title');
    expect(wrapper.find('.bg-border').exists()).toBe(false);
    expect(wrapper.findAll('ul li')).toHaveLength(1);
    expect(wrapper.text()).toContain('Visos užduotys');
  });

  it('links to the remaining task count when the home page has more tasks', () => {
    const wrapper = mount(AttentionQueue, {
      props: { tasks: [task()], stats: { ...stats, total: 4 }, remainingCount: 3, moreHref: '/mano/tasks' },
    });

    const moreLink = wrapper.find('header a');
    expect(moreLink.attributes('href')).toBe('/mano/tasks');
    expect(moreLink.text()).toBe('ir dar :count');
  });

  it('links a task straight to the screen that finishes it, with what it is about', () => {
    const wrapper = mountQueue([task()]);
    const row = wrapper.find('ul a');

    expect(row.attributes('href')).toBe('/mano/meetings/m1?tab=agenda');
    expect(wrapper.text()).toContain('Užpildyti darbotvarkę');
    expect(wrapper.text()).toContain('Senato posėdis');
  });

  it('says how late an overdue task is and shows upcoming dates as relative, colouring only overdue dates', () => {
    const wrapper = mountQueue([
      task({ id: '1', is_overdue: true, due_date: new Date(Date.now() - 10 * day).toISOString() }),
      task({ id: '2', due_date: new Date(Date.now() + 3 * day).toISOString() }),
    ], { total: 2, overdue: 1 });

    const dates = wrapper.findAll('time');
    expect(wrapper.find('[data-slot="status-badge"]').exists()).toBe(false);
    expect(dates[0].text()).toBe('home.overdue_by');
    expect(dates[0].text()).not.toContain('prieš');
    expect(dates[0].classes()).toContain('text-brand');
    expect(dates[0].attributes('aria-label')).toBe('home.overdue_due_date');
    expect(dates[1].text()).toContain('po 3 d.');
    expect(dates[1].classes()).toContain('text-muted-foreground');
  });

  it('uses the current language for relative dates', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ app: { locale: 'en' } }) as ReturnType<typeof usePage>);

    const wrapper = mountQueue([task({ due_date: new Date(Date.now() + 3 * day).toISOString() })]);

    expect(wrapper.find('time').text()).toContain('in 3d');
  });

  it('gives an on-time task no badge', () => {
    const wrapper = mountQueue([task()]);

    expect(wrapper.find('[data-slot="status-badge"]').exists()).toBe(false);
  });

  it('distinguishes no open tasks from no tasks due soon', () => {
    const wrapper = mountQueue([], { total: 0 });

    expect(wrapper.text()).toContain('Šiuo metu nieko nelaukia');
    expect(mountQueue([], { total: 2 }).text()).toContain('Artimiausiu metu užduočių nėra');
  });

  it('hands an empty queue to the page\'s all-clear list instead of showing its own section', () => {
    const registry = { set: vi.fn(), remove: vi.fn() };
    const wrapper = mount(AttentionQueue, {
      props: { tasks: [], stats: { ...stats, total: 0 }, moreHref: '/mano/tasks' },
      global: { provide: { [OVERVIEW_STATUS_KEY as symbol]: registry } },
    });

    expect(wrapper.find('[data-slot="attention-queue"]').exists()).toBe(false);
    expect(registry.set).toHaveBeenCalledWith(expect.any(String), expect.objectContaining({
      title: 'home.tasks_title',
      emptyText: 'Šiuo metu nieko nelaukia',
    }));
  });

  it('renders a task with nothing left to link to as plain text, not a dead link', () => {
    const wrapper = mount(AttentionQueue, {
      props: { tasks: [task({ taskable: null, taskable_type: 'unknown' })], stats, moreHref: '/mano/tasks' },
    });

    expect(wrapper.text()).toContain('Užpildyti darbotvarkę');
  });
});
