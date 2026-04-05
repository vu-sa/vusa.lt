import type { Meta, StoryObj } from '@storybook/vue3-vite';

import { Alert, AlertTitle, AlertDescription } from './';

const meta: Meta<typeof Alert> = {
  title: 'UI/Alert',
  component: Alert,
  argTypes: {
    variant: { control: 'select', options: ['default', 'destructive'] },
  },
  args: {
    variant: 'default',
  },
};

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: args => ({
    components: { Alert, AlertTitle, AlertDescription },
    setup() {
      return { args };
    },
    template: `<Alert v-bind="args">
    <AlertTitle>This is a default alert!</AlertTitle>
    <AlertDescription>This is a description of the alert.</AlertDescription>
</Alert>`,
  }),
};

export const Destructive: Story = {
  args: {
    variant: 'destructive',
  },
  render: args => ({
    components: { Alert, AlertTitle, AlertDescription },
    setup() {
      return { args };
    },
    template: `<Alert v-bind="args">
    <AlertTitle>This is a destructive alert!</AlertTitle>
    <AlertDescription>This is a description of the alert.</AlertDescription>
    </Alert>`,
  }),
};

export const WithTitleAndDescription: Story = {
  render: args => ({
    components: { Alert, AlertTitle, AlertDescription },
    setup() {
      return { args };
    },
    template: `
      <Alert v-bind="args">
        <AlertTitle>This is the title</AlertTitle>
        <AlertDescription>This is the description</AlertDescription>
      </Alert>
    `,
  }),
};
