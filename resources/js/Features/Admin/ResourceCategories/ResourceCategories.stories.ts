import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { ref } from 'vue';

import ResourceCategorySheetForm from './ResourceCategorySheetForm.vue';

const meta: Meta = {
  title: 'Reservations/Resource categories',
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' } },
};

export default meta;
type Story = StoryObj;

export const Create: Story = {
  render: () => ({
    components: { ResourceCategorySheetForm },
    setup: () => ({ open: ref(true) }),
    template: '<ResourceCategorySheetForm v-model:open="open" />',
  }),
};

export const Edit: Story = {
  render: () => ({
    components: { ResourceCategorySheetForm },
    setup: () => ({
      open: ref(true),
      category: {
        id: 4,
        name: { lt: 'Projektoriai', en: 'Projectors' },
        description: { lt: 'Projektoriai renginiams ir paskaitoms.', en: 'Projectors for events and lectures.' },
        icon: 'video-24-regular',
      },
    }),
    template: '<ResourceCategorySheetForm v-model:open="open" :category="category" />',
  }),
};
