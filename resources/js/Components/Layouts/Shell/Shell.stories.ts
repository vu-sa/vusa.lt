import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { expect, userEvent, within } from 'storybook/test';

import MobileBottomBar from './MobileBottomBar.vue';
import MobileMenuPanel from './MobileMenuPanel.vue';
import SectionTabs from './SectionTabs.vue';
import WorkspacePicker from './WorkspacePicker.vue';
import { atstovavimas, pradzia, rezervacijos, workspace, section } from './__tests__/fixtures';

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

    await expect(await within(document.body).findByText('Patalpos, įranga, reklama')).toBeVisible();
  },
};

export const MobileBottomBarStory: Story = {
  name: 'MobileBottomBar',
  parameters: { viewport: { defaultViewport: 'mobile1' } },
  render: () => ({
    components: { MobileBottomBar },
    setup: () => ({ primary: atstovavimas, activeWorkspace: atstovavimas, activeSection: atstovavimas.sections[1] }),
    template: `
      <div class="w-[390px] bg-background text-foreground [--shell-bottom-bar:3.5rem]">
        <MobileBottomBar :primary :active-workspace :active-section can-create />
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
