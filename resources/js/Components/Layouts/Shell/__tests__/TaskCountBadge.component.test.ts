import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { reactive } from 'vue';

import MobileBottomBar from '../MobileBottomBar.vue';
import SectionTabs from '../SectionTabs.vue';
import TaskCountBadge from '../TaskCountBadge.vue';
import WorkspacePicker from '../WorkspacePicker.vue';

import { atstovavimas, pradzia } from './fixtures';

const page = reactive({ props: { auth: { user: { tasks_count: 0, overdue_tasks_count: 0 } } } });

vi.mock('@inertiajs/vue3', async () => ({
  ...(await import('@/mocks/inertia.mock')),
  usePage: () => page,
}));

const setTasks = (pending: number, overdue = 0) => {
  page.props.auth.user = { tasks_count: pending, overdue_tasks_count: overdue };
};

describe('TaskCountBadge', () => {
  beforeEach(() => setTasks(0));

  it('renders nothing when no task is waiting', () => {
    expect(mount(TaskCountBadge).find('[data-slot="task-count-badge"]').exists()).toBe(false);
  });

  it('shows the pending count in the attention role', () => {
    setTasks(3);
    const badge = mount(TaskCountBadge).find('[data-slot="task-count-badge"]');

    expect(badge.text()).toContain('3');
    expect(badge.attributes('data-status-role')).toBe('attention');
    expect(badge.text()).toContain('shell.badges.tasks_pending');
  });

  it('turns danger once any of them is overdue, and says so to assistive tech', () => {
    setTasks(3, 1);
    const badge = mount(TaskCountBadge).find('[data-slot="task-count-badge"]');

    expect(badge.attributes('data-status-role')).toBe('danger');
    expect(badge.text()).toContain('shell.badges.tasks_overdue');
  });

  it('caps a very long list', () => {
    setTasks(140);

    expect(mount(TaskCountBadge).text()).toContain('99+');
  });
});

describe('where the badge sits', () => {
  beforeEach(() => setTasks(2));

  it('is on the Užduotys tab of Pradžia, and on no other tab', () => {
    const tabs = mount(SectionTabs, { props: { workspace: pradzia, activeSection: pradzia.sections[0] } }).findAll('li a');

    expect(tabs.map(tab => tab.find('[data-slot="task-count-badge"]').exists())).toEqual([false, true, false]);
  });

  it('is on the mobile Užduotys tab', () => {
    const links = mount(MobileBottomBar, { props: { activeWorkspace: pradzia } }).findAll('a');

    expect(links.map(link => link.find('[data-slot="task-count-badge"]').exists())).toEqual([false, true, false]);
  });

  it('is on the workspace picker trigger only while Mano is the current workspace', () => {
    const stubs = {
      Popover: { template: '<div><slot /></div>' },
      PopoverTrigger: { template: '<div data-testid="trigger"><slot /></div>' },
      PopoverContent: { template: '<div data-testid="panel"><slot /></div>' },
    };
    const triggerBadge = (activeWorkspace: typeof pradzia) => mount(WorkspacePicker, {
      props: { workspaces: [pradzia, atstovavimas], activeWorkspace },
      global: { stubs },
    }).find('[data-testid="trigger"] [data-slot="task-count-badge"]').exists();

    expect(triggerBadge(pradzia)).toBe(true);
    expect(triggerBadge(atstovavimas)).toBe(false);
  });
});
