import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { expect, userEvent, within } from 'storybook/test';

import MobileBottomBar from './MobileBottomBar.vue';
import MobileMenuPanel from './MobileMenuPanel.vue';
import SectionTabs from './SectionTabs.vue';
import WorkspacePicker from './WorkspacePicker.vue';
import { atstovavimas, pradzia, rezervacijos, workspace, section } from './__tests__/fixtures';

import { usePage } from '@/mocks/inertia.storybook';

const svetaine = workspace('svetaine', ['puslapiai', 'naujienos', 'kalendorius', 'baneriai', 'dokumentai', 'darbotvarkes_klausimai']
  .map(key => section(key, `${key}.index`)));

const workspaces = [pradzia, atstovavimas, rezervacijos, svetaine];

const meta: Meta = {
  title: 'Layouts/Admin shell',
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' }, layout: 'fullscreen' },
};

export default meta;
type Story = StoryObj;

export const SectionTabsRow: Story = {
  render: () => ({
    components: { SectionTabs },
    setup: () => ({ workspace: svetaine, activeSection: svetaine.sections[1] }),
    template: '<div class="w-[390px] bg-background text-foreground"><SectionTabs :workspace :active-section /></div>',
  }),
};

export const WorkspacePickerOpen: Story = {
  render: () => ({
    components: { WorkspacePicker },
    setup: () => ({ workspaces, activeWorkspace: atstovavimas, activeSection: atstovavimas.sections[1] }),
    template: `
      <div class="h-[28rem] bg-background p-6 text-foreground">
        <WorkspacePicker :workspaces :active-workspace :active-section show-all-sections />
      </div>
    `,
  }),
  play: async ({ canvasElement }) => {
    await userEvent.hover(within(canvasElement).getByRole('button'));

    await expect(await within(document.body).findByText('Įrangos ir daiktų skolinimas')).toBeVisible();
  },
};

export const MobileBottomBarStory: Story = {
  name: 'MobileBottomBar',
  parameters: { viewport: { defaultViewport: 'mobile1' } },
  render: () => ({
    components: { MobileBottomBar },
    setup: () => ({ activeWorkspace: atstovavimas, activeSection: atstovavimas.sections[1] }),
    template: `
      <div class="w-[390px] bg-background text-foreground [--shell-bottom-bar:3.5rem]">
        <MobileBottomBar :active-workspace :active-section can-create />
      </div>
    `,
  }),
};

export const MobileMenu: Story = {
  parameters: { viewport: { defaultViewport: 'mobile1' } },
  render: () => ({
    components: { MobileMenuPanel },
    setup: () => ({ workspaces, activeWorkspace: atstovavimas, activeSection: atstovavimas.sections[1] }),
    template: '<MobileMenuPanel :open="true" :workspaces :active-workspace :active-section show-all-sections />',
  }),
};

const withTasks = (pending: number, overdue: number) => (story: () => unknown) => {
  usePage.mockImplementation(() => ({ props: { auth: { user: { tasks_count: pending, overdue_tasks_count: overdue } } } }));

  return story();
};

const taskTabsRender = () => ({
  components: { SectionTabs },
  setup: () => ({ workspace: pradzia, activeSection: pradzia.sections[0] }),
  template: '<div class="w-[390px] bg-background text-foreground"><SectionTabs :workspace :active-section /></div>',
});

/** Pending tasks count on the Užduotys tab — `attention` while none is late. */
export const TaskBadgePending: Story = {
  decorators: [withTasks(4, 0)],
  render: taskTabsRender,
};

/** One late task turns the whole count `danger`, and adds an icon so colour is not the only cue. */
export const TaskBadgeOverdue: Story = {
  decorators: [withTasks(4, 1)],
  render: taskTabsRender,
};
