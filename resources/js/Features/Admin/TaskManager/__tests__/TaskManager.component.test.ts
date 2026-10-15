import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

import TaskManager from '../TaskManager.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';
import type { TaskDisplayData } from '@/Composables/useTaskPresentation';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('vue-sonner', () => ({ toast: { info: vi.fn(), success: vi.fn(), error: vi.fn() } }));

// What is asserted here is TaskManager's own wiring: the rows it draws, the confirmation gate
// and the request it finally sends.
const task: TaskDisplayData = {
  id: 'task-1',
  name: 'Sutvarkyti dokumentus',
  taskable_type: 'meeting',
  taskable_id: 'meeting-1',
  taskable: { id: 'meeting-1', name: 'Posėdis' },
  completed_at: null,
  can_delete: true,
};

// The AlertDialog is deliberately NOT stubbed. Its action button closes the dialog through
// reka-ui before any handler of ours runs, and that ordering is the whole reason confirming a
// deletion used to do nothing — a stub that only renders a button cannot reproduce it.
let wrapper: ReturnType<typeof mount>;

const mountManager = (props: Record<string, unknown> = {}) => {
  wrapper = mount(TaskManager, {
    props: { tasks: [task], ...props },
    global: { stubs: { ...commonStubs } },
  });

  return wrapper;
};

/** AlertDialogContent teleports out of the wrapper, so reach its buttons through the document. */
const dialogButton = (label: string) =>
  [...document.body.querySelectorAll('button')].find(button => button.textContent?.includes(label));

beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage());
  vi.mocked(router.delete).mockClear();
  vi.mocked(toast.success).mockClear();
});

// Teleported dialog content outlives the test that rendered it, so a later lookup in the body
// would otherwise find a stale button belonging to an unmounted component.
afterEach(() => {
  wrapper?.unmount();
  document.body.innerHTML = '';
});

describe('deleting a task', () => {
  it('asks for confirmation before sending the request', async () => {
    const wrapper = mountManager();

    wrapper.findComponent({ name: 'TaskRow' }).vm.$emit('action', 'delete');
    await wrapper.vm.$nextTick();

    expect(router.delete).not.toHaveBeenCalled();
    expect(dialogButton('forms.delete')).toBeDefined();
  });

  it('sends the delete once the confirmation is accepted', async () => {
    const wrapper = mountManager();

    wrapper.findComponent({ name: 'TaskRow' }).vm.$emit('action', 'delete');
    await wrapper.vm.$nextTick();

    dialogButton('forms.delete')!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    await wrapper.vm.$nextTick();

    expect(router.delete).toHaveBeenCalledWith(
      expect.stringContaining('tasks.destroy'),
      expect.objectContaining({ preserveScroll: true }),
    );
  });

  it('leaves the success toast to the controller flash', async () => {
    // The controller flashes a success message and useToasts shows it globally, so a toast
    // here as well meant every deletion announced itself twice.
    const wrapper = mountManager();

    wrapper.findComponent({ name: 'TaskRow' }).vm.$emit('action', 'delete');
    await wrapper.vm.$nextTick();
    dialogButton('forms.delete')!.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    await wrapper.vm.$nextTick();

    const options = vi.mocked(router.delete).mock.calls[0][1] as { onSuccess?: () => void };
    expect(options.onSuccess).toBeUndefined();
    options.onSuccess?.();

    expect(toast.success).not.toHaveBeenCalled();
  });
});

describe('the list', () => {
  it('draws one task row per open task, and leaves completed ones behind the filter', () => {
    const done: TaskDisplayData = { ...task, id: 'task-2', completed_at: '2026-01-01T00:00:00Z' };
    const rows = mountManager({ tasks: [task, done] }).findAllComponents({ name: 'TaskRow' });

    expect(rows).toHaveLength(1);
    expect(rows[0].props('task')).toEqual(task);
  });

  it('opens the task details from the row', () => {
    const wrapper = mountManager();

    wrapper.findComponent({ name: 'TaskRow' }).vm.$emit('open');

    expect(wrapper.emitted('openTaskDetail')?.[0]).toEqual([task]);
  });
});

describe('show all', () => {
  it('lists open tasks by default and adds the completed ones when switched on', async () => {
    const done: TaskDisplayData = { ...task, id: 'task-2', completed_at: '2026-01-01T00:00:00Z' };
    const wrapper = mountManager({ tasks: [task, done] });
    const rowIds = () => wrapper.findAllComponents({ name: 'TaskRow' }).map(row => row.props('task').id);
    const toggle = wrapper.get('button[aria-pressed]');

    expect(rowIds()).toEqual(['task-1']);
    expect(toggle.attributes('aria-pressed')).toBe('false');

    await toggle.trigger('click');

    expect(rowIds()).toEqual(['task-1', 'task-2']);
    expect(toggle.attributes('aria-pressed')).toBe('true');
  });
});
