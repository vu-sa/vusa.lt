import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import IndexTask from '../IndexTask.vue';

import type { TaskDisplayData } from '@/Composables/useTaskPresentation';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

/** The collection shell is covered by its own suite; here it only hands over its slots and events. */
const CollectionPageStub = {
  name: 'CollectionPage',
  props: ['source', 'quickFilters', 'title', 'eyebrow', 'view'],
  emits: ['quickFilter'],
  template: `
    <div>
      <div data-testid="actions"><slot name="actions" /></div>
      <div v-for="item in source.items.value" :key="item.id" data-testid="row"><slot name="row" :item="item" :view="view ?? 'rows'" /></div>
    </div>
  `,
};

const task = (overrides: Partial<TaskDisplayData> = {}): TaskDisplayData => ({
  id: 'task-1',
  name: 'Įkelti protokolą',
  taskable_type: 'meeting',
  taskable_id: 'meeting-1',
  taskable: { id: 'meeting-1', name: 'Senato posėdis' },
  completed_at: null,
  can_be_manually_completed: true,
  ...overrides,
});

const counts = { pending: 4, overdue: 2, auto: 1, assigned: 3, completed: 5 };

const mountPage = (props: Record<string, unknown> = {}) => mount(IndexTask, {
  props: {
    scope: 'mine',
    data: [task()],
    meta: { total: 1, per_page: 50, current_page: 1, last_page: 1 },
    taskCounts: counts,
    canViewAllTasks: false,
    tenants: [],
    ...props,
  },
  global: { stubs: { ...commonStubs, CollectionPage: CollectionPageStub } },
});

const quickFilterLabels = (wrapper: ReturnType<typeof mount>) =>
  (wrapper.findComponent(CollectionPageStub).props('quickFilters') as { label: string }[]).map(filter => filter.label);

describe('IndexTask', () => {
  it('offers the way to all tasks only on the personal list of a user who may read them', () => {
    expect(mountPage().find('[data-testid="actions"] a').exists()).toBe(false);
    expect(mountPage({ canViewAllTasks: true }).find('[data-testid="actions"] a').text()).toContain('tasks.collection.all_tasks');
    expect(mountPage({ scope: 'tenant', canViewAllTasks: true }).find('[data-testid="actions"] a').exists()).toBe(false);
  });

  it('puts the counts on the quick filters, with "assigned to me" only in the padalinys scope', () => {
    expect(quickFilterLabels(mountPage())).toEqual([
      'tasks.collection.overdue · 2',
      'tasks.collection.automatic · 1',
      'tasks.collection.completed · 5',
    ]);
    expect(quickFilterLabels(mountPage({ scope: 'tenant' }))).toContain('tasks.collection.assigned_to_me · 3');
  });

  it('leads with a linked task that is not on the first page, so the preview can open on it', () => {
    const linked = task({ id: 'task-99', name: 'Susietoji' });
    const rows = mountPage({ linkedTask: linked }).findAll('[data-testid="row"]');

    expect(rows[0].text()).toContain('Susietoji');
    expect(rows).toHaveLength(2);
  });

  it('completes a task in place and reloads only the counts', async () => {
    const wrapper = mountPage();

    await wrapper.find('[data-slot="task-completion-control"] button').trigger('click');

    expect(router.post).toHaveBeenCalledWith(
      expect.stringContaining('tasks.updateCompletionStatus'),
      { completed: true },
      expect.objectContaining({ only: ['taskCounts'] }),
    );
    expect(wrapper.find('[data-testid="row"] .line-through').exists()).toBe(true);
  });

  it('leaves the actions to the preview pane beside it', () => {
    const task = { taskable_type: 'institution', action_type: 'periodicity_gap', can_be_manually_completed: false, can_delete: true };
    const rowActions = (view: string) => mount(IndexTask, {
      props: {
        scope: 'mine',
        data: [{ id: 'task-1', name: 'Pranešti', taskable_id: 'i-1', taskable: { id: 'i-1', name: 'MIF SA' }, completed_at: null, ...task }],
        meta: { total: 1, per_page: 50, current_page: 1, last_page: 1 },
        taskCounts: counts,
        canViewAllTasks: false,
        tenants: [],
      },
      global: { stubs: { ...commonStubs, CollectionPage: { ...CollectionPageStub, props: [...CollectionPageStub.props], setup: () => ({ view }) } } },
    }).find('[data-slot="collection-row-actions"]').exists();

    expect(rowActions('rows')).toBe(true);
    expect(rowActions('preview')).toBe(false);
  });
});
