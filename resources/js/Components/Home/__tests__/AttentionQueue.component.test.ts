import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import AttentionQueue from '../AttentionQueue.vue';
import type { HomeTask } from '../types';

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
  it('is the ink band while there is something to do, headed by how many things', () => {
    const wrapper = mountQueue([task()]);

    expect(wrapper.find('.bg-foreground').exists()).toBe(true);
    expect(wrapper.find('h2').text()).toContain('Užduočių: :count');
    expect(wrapper.text()).toContain('Visos užduotys');
  });

  it('links a task straight to the screen that finishes it, with what it is about', () => {
    const wrapper = mountQueue([task()]);
    const row = wrapper.find('ul a');

    expect(row.attributes('href')).toBe('/mano/meetings/m1?tab=agenda');
    expect(wrapper.text()).toContain('Užpildyti darbotvarkę');
    expect(wrapper.text()).toContain('Senato posėdis');
  });

  it('marks an overdue task as danger and a task due within a week as attention', () => {
    const wrapper = mountQueue([
      task({ id: '1', is_overdue: true, due_date: new Date(Date.now() - 2 * day).toISOString() }),
      task({ id: '2', due_date: new Date(Date.now() + 3 * day).toISOString() }),
    ], { total: 2, overdue: 1 });

    const roles = wrapper.findAll('[data-slot="status-badge"]').map(badge => badge.attributes('data-status-role'));
    expect(roles).toEqual(['danger', 'attention']);
  });

  it('gives an on-time task no badge — only what needs attention is painted', () => {
    const wrapper = mountQueue([task()]);

    expect(wrapper.find('[data-slot="status-badge"]').exists()).toBe(false);
  });

  it('replaces the band with a teaching empty state when nothing is waiting', () => {
    const wrapper = mountQueue([], { total: 0 });

    expect(wrapper.find('.bg-foreground').exists()).toBe(false);
    expect(wrapper.find('[data-slot="empty-state"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Šiuo metu nieko nelaukia');
  });

  it('renders a task with nothing left to link to as plain text, not a dead link', () => {
    const wrapper = mount(AttentionQueue, {
      props: { tasks: [task({ taskable: null, taskable_type: 'unknown' })], stats, moreHref: '/mano/tasks' },
    });

    expect(wrapper.text()).toContain('Užpildyti darbotvarkę');
  });
});
