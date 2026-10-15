import type { Meta, StoryObj } from '@storybook/vue3-vite';

import StatusBadge from './StatusBadge.vue';

import {
  contentStatuses,
  reservationResourceStatuses,
  studentBenefitStatuses,
  supportRequestStatuses,
  taskStatuses,
  voteStatuses,
} from '@/Constants/statuses';

const meta: Meta<typeof StatusBadge> = {
  title: 'Patterns/StatusBadge',
  component: StatusBadge,
  tags: ['autodocs'],
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' } },
};

export default meta;
type Story = StoryObj<typeof meta>;

export const AllStatusFamilies: Story = {
  render: () => ({
    components: { StatusBadge },
    setup: () => ({
      groups: [
        ['Rezervacijos', reservationResourceStatuses],
        ['Balsai', voteStatuses],
        ['Nauda studentams', studentBenefitStatuses],
        ['Užduotys', taskStatuses],
        ['Turinys', contentStatuses],
        ['Pagalba', supportRequestStatuses],
      ],
    }),
    template: `
      <div class="flex flex-col gap-6 bg-background p-6 text-foreground">
        <section v-for="([title, statuses]) in groups" :key="title" class="flex flex-col gap-2">
          <h2 class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ title }}</h2>
          <div class="flex flex-wrap gap-2">
            <StatusBadge v-for="(status, key) in statuses" :key="key" :status="status" />
          </div>
        </section>
      </div>
    `,
  }),
};
