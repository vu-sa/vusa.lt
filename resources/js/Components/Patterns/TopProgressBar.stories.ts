import type { Meta, StoryObj } from '@storybook/vue3-vite';

import TopProgressBar from './TopProgressBar.vue';

const meta: Meta<typeof TopProgressBar> = {
  title: 'Patterns/TopProgressBar',
  component: TopProgressBar,
  tags: ['autodocs'],
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' } },
};

export default meta;
type Story = StoryObj<typeof meta>;

export const IndeterminateLoading: Story = {
  render: () => ({
    components: { TopProgressBar },
    template: `
      <div class="p-6 bg-background text-foreground max-w-xl mx-auto space-y-4">
        <h3 class="text-xs uppercase font-medium text-muted-foreground">Kolekcijos krovimo eigos juosta (indeterminate)</h3>
        <div class="border border-border p-4 bg-card relative">
          <TopProgressBar indeterminate class="absolute top-0 left-0 right-0" />
          <p class="text-sm text-muted-foreground pt-2">Lentelės turinys atnaujinamas fone...</p>
        </div>
      </div>
    `,
  }),
};

export const DeterminateLoading: Story = {
  render: () => ({
    components: { TopProgressBar },
    template: `
      <div class="p-6 bg-background text-foreground max-w-xl mx-auto space-y-4">
        <h3 class="text-xs uppercase font-medium text-muted-foreground">Apibrėžta eigos juosta (65%)</h3>
        <div class="border border-border p-4 bg-card relative">
          <TopProgressBar :indeterminate="false" :model-value="65" class="absolute top-0 left-0 right-0" />
          <p class="text-sm text-muted-foreground pt-2">Failų įkėlimas: 65%</p>
        </div>
      </div>
    `,
  }),
};
