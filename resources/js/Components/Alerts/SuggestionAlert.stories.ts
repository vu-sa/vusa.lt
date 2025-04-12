import type { Meta, StoryObj } from '@storybook/vue3';
import SuggestionAlert from './SuggestionAlert.vue';
import { trans as $t } from 'laravel-vue-i18n';

const meta: Meta<typeof SuggestionAlert> = {
  title: 'Alerts/SuggestionAlert',
  component: SuggestionAlert,
  argTypes: {
    modelValue: { control: 'boolean' },
  },
  args: {
    modelValue: true,
  },
};

export default meta;

type Story = StoryObj<typeof meta>;

// TODO: SuggestionAlert has a $t() function that is not being used in the storybook
export const Default: Story = {
  render: (args) => ({
    components: { SuggestionAlert },
    setup() {
      return { args };
    },
    template: '<SuggestionAlert v-bind="args">This is a suggestion alert!</SuggestionAlert>',
  }),
};