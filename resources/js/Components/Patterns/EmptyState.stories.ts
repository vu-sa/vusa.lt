import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { Calendar, Inbox } from 'lucide-vue-next';

import EmptyState from './EmptyState.vue';

const meta: Meta<typeof EmptyState> = {
  title: 'Patterns/EmptyState',
  component: EmptyState,
  tags: ['autodocs'],
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' } },
};

export default meta;
type Story = StoryObj<typeof meta>;

export const FirstUseTeaching: Story = {
  args: {
    mode: 'empty',
    title: 'Nėra sukurtų posėdžių',
    description: 'Užfiksuok savo institucijos posėdį, kad repai galėtų sužinoti darbotvarkę ir balsavimo rezultatus.',
    actionLabel: 'Naujas posėdis',
    docsLabel: 'Kaip pildyti posėdžio duomenis?',
    docsHref: 'https://vusa.lt/docs/posedziai',
  },
};

export const FirstUseWithCustomIcon: Story = {
  render: () => ({
    components: { EmptyState, Calendar },
    setup() {
      return { Calendar };
    },
    template: `
      <EmptyState
        mode="empty"
        title="Nėra aktyvių rezervacijų"
        description="Šiuo metu neturi jokių aktyvių išteklių rezervacijų savo padalinyje."
        :icon="Calendar"
        action-label="Rezervuoti išteklių"
      />
    `,
  }),
};

export const NoResultsFiltered: Story = {
  args: {
    mode: 'no-results',
    title: 'Pagal pasirinktus filtrus nieko nerasta',
    description: 'Bandyk pakeisti paieškos frazę arba išvalyti pasirinktus filtrus.',
    clearLabel: 'Išvalyti visus filtrus',
  },
};

export const MinimalEmpty: Story = {
  render: () => ({
    components: { EmptyState, Inbox },
    setup() {
      return { Inbox };
    },
    template: `
      <EmptyState
        title="Gautieji tušti"
        description="Šiuo metu jokių naujų užklausų nėra."
        :icon="Inbox"
      />
    `,
  }),
};
