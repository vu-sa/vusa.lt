import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { ref } from 'vue';

import { DatePicker, DateTimePicker } from '@/Components/ui/date-picker';
import { TimePicker } from '@/Components/ui/time-picker';

const meta: Meta = {
  title: 'Patterns/Admin Pickers',
  tags: ['autodocs'],
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' } },
};

export default meta;
type Story = StoryObj;

export const Controls: Story = {
  render: () => ({
    components: { DatePicker, DateTimePicker, TimePicker },
    setup() {
      const date = ref(new Date(Date.UTC(2026, 8, 19, 12)));
      const dateTime = ref(new Date(2026, 8, 19, 18, 0));
      const time = ref({ hour: 18, minute: 0 });

      return { date, dateTime, time };
    },
    template: `
      <div class="grid max-w-xl gap-4 bg-background p-6 text-foreground">
        <DatePicker v-model="date" clearable />
        <TimePicker v-model="time" clearable :minute-step="15" />
        <DateTimePicker v-model="dateTime" :minute-step="15" />
      </div>
    `,
  }),
};
