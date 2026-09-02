import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import TaskCard from '../TaskCard.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';
import type { TaskDisplayData } from '@/Composables/useTaskPresentation';
import { TaskActionType } from '@/Types/TaskTypes';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('vue-sonner', () => ({ toast: { info: vi.fn(), success: vi.fn(), error: vi.fn() } }));

const makeTask = (overrides: Partial<TaskDisplayData> = {}): TaskDisplayData => ({
  id: 'task-1',
  name: 'Sutvarkyti dokumentus',
  taskable_type: 'meeting',
  taskable_id: 'meeting-1',
  taskable: { id: 'meeting-1', name: 'Posėdis' },
  completed_at: null,
  ...overrides,
});

const mountCard = (task: TaskDisplayData) =>
  mount(TaskCard, {
    props: { task },
    global: { stubs: { ...commonStubs } },
  });

const deleteButton = (wrapper: ReturnType<typeof mount>) =>
  wrapper.findAll('button').find(button => button.text().includes('Delete') || button.text().includes('tasks.delete_automatic'));

beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage());
});

describe('delete affordance', () => {
  it('offers deletion when the backend says the action is available', () => {
    const wrapper = mountCard(makeTask({ can_delete: true, can_be_manually_completed: false }));

    expect(deleteButton(wrapper)).toBeDefined();
  });

  it('hides deletion when the backend says it would be refused', () => {
    // Everyone used to see a Delete they could not use — tasks.delete is seeded for no role.
    const wrapper = mountCard(makeTask({ can_delete: false }));

    expect(deleteButton(wrapper)).toBeUndefined();
  });

  it('emits delete rather than deleting on its own', async () => {
    const task = makeTask({ can_delete: true });
    const wrapper = mountCard(task);

    await deleteButton(wrapper)!.trigger('click');

    expect(wrapper.emitted('delete')?.[0]).toEqual([task]);
  });
});

describe('orphaned tasks', () => {
  it('names the missing subject instead of linking to nothing', () => {
    const wrapper = mountCard(makeTask({ taskable: null, action_type: TaskActionType.AgendaCompletion }));

    expect(wrapper.text()).toContain('tasks.orphaned');
    expect(wrapper.findComponent({ name: 'Link' }).exists()).toBe(false);
  });
});
