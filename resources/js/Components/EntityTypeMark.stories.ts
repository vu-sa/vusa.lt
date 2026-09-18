import type { Meta, StoryObj } from '@storybook/vue3-vite';

import EntityTypeMark from './EntityTypeMark.vue';

import { entityTypeRegistry } from '@/Constants/entityTypes';

const meta: Meta<typeof EntityTypeMark> = {
  title: 'Components/EntityTypeMark',
  component: EntityTypeMark,
  tags: ['autodocs'],
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' } },
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Registry: Story = {
  render: () => ({
    components: { EntityTypeMark },
    setup: () => ({ definitions: Object.values(entityTypeRegistry) }),
    template: `
      <div class="grid gap-x-8 gap-y-3 bg-background p-6 text-foreground sm:grid-cols-2 lg:grid-cols-3">
        <EntityTypeMark
          v-for="definition in definitions"
          :key="definition.type"
          :type="definition.type"
        />
      </div>
    `,
  }),
};
