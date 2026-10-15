import type { Meta, StoryObj } from '@storybook/vue3-vite';

import RuledGrid from './RuledGrid.vue';

const meta: Meta<typeof RuledGrid> = {
  title: 'Brand/RuledGrid',
  component: RuledGrid,
  tags: ['autodocs'],
  parameters: { a11y: { test: 'error' } },
};

export default meta;
type Story = StoryObj<typeof meta>;

const renderCells = (count: number) => (args: Record<string, unknown>) => ({
  components: { RuledGrid },
  setup: () => ({ args, cells: Array.from({ length: count }, (_, index) => index + 1) }),
  template: '<RuledGrid v-bind="args"><div v-for="cell in cells" :key="cell" class="p-5">Cell {{ cell }}</div></RuledGrid>',
});

export const FullRows: Story = {
  args: { columns: { base: 2, sm: 3, lg: 5 }, align: 'center', topRule: 'full' },
  render: renderCells(10),
};

export const PartialLastRow: Story = {
  args: { columns: { base: 2, sm: 3, lg: 5 }, align: 'center', topRule: 'full' },
  render: renderCells(7),
};

export const ContentWidthTopRule: Story = {
  args: { columns: { base: 2 }, topRule: 'cells' },
  render: renderCells(3),
};

export const RecordFacts: Story = {
  args: { columns: { base: 1, sm: 2, lg: 3, xl: 5 } },
  globals: { surface: 'admin' },
  render: renderCells(5),
};
