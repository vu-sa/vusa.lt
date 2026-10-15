import type { Meta, StoryObj } from '@storybook/vue3-vite';

import OverviewChart from './OverviewChart.vue';
import OverviewNumbers from './OverviewNumbers.vue';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';

const meta: Meta = {
  title: 'Overview/Apžvalga',
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' }, layout: 'fullscreen' },
};

export default meta;
type Story = StoryObj;

const numbers = [
  { key: 'overdue', label: 'Vėluoja', value: 2, href: '/mano/institutions', tone: 'danger' },
  { key: 'approaching', label: 'Artėja terminas', value: 3, href: '/mano/institutions', tone: 'attention' },
  { key: 'incomplete', label: 'Neužpildyti posėdžiai', value: 0, href: '/mano/meetings', tone: 'attention' },
  { key: 'tasks', label: 'Atviros užduotys', value: 5, href: '/mano/tasks/summary' },
];

export const NumbersAsLinks: Story = {
  render: () => ({
    components: { OverviewNumbers },
    setup: () => ({ numbers }),
    template: '<div class="max-w-3xl bg-background p-6 text-foreground"><OverviewNumbers :numbers="numbers" /></div>',
  }),
};

export const FullPage: Story = {
  render: () => ({
    components: { OverviewPage, OverviewNumbers, OverviewChart, OverviewSection },
    setup: () => ({ numbers }),
    template: `
      <div class="max-w-4xl bg-background p-6 text-foreground">
        <OverviewPage eyebrow="ViSAK" title="Apžvalga" lead="Stebėk savo institucijų atstovavimo veiklą.">
          <OverviewNumbers :numbers="numbers" />
          <OverviewSection title="Būklės pokyčiai">
            <OverviewChart summary="Per 30 d. vėluojančių institucijų sumažėjo nuo 4 iki 2.">
              <div class="h-24 border border-border" />
            </OverviewChart>
          </OverviewSection>
          <OverviewSection title="Artimiausi posėdžiai" empty empty-text="artimiausiu metu nieko nesuplanuota" />
        </OverviewPage>
      </div>`,
  }),
};

export const FullPageDark: Story = {
  ...FullPage,
  globals: { surface: 'admin', theme: 'dark' },
};
