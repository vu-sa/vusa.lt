import { fn } from '@storybook/test';
import type { Meta, StoryObj } from '@storybook/vue3';

import { Button } from "./";

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta: Meta<typeof Button> = {
  title: 'Shadcn/Button',
  component: Button,
  // This component will have an automatically generated docsPage entry: https://storybook.js.org/docs/writing-docs/autodocs
  tags: ['autodocs'],
  argTypes: {
    variant: { control: 'select', options: ['default', 'destructive', 'outline', 'secondary', 'ghost', 'link'] },
    size: { control: 'select', options: ['default', 'xs', 'sm', 'lg', 'xl', '2xl', 'icon'] },
  },
  args: {
    variant: 'default',
    size: 'default',
    // Use `fn` to spy on the onClick arg, which will appear in the actions panel once invoked: https://storybook.js.org/docs/essentials/actions#action-args
    onClick: fn(),
  },
};

export default meta;

type Story = StoryObj<typeof meta>;
/*
 *👇 Render functions are a framework specific feature to allow you control on how the component renders.
 * See https://storybook.js.org/docs/api/csf
 * to learn how to use render functions.
 */
export const Primary: Story = {
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Button</Button>',
  }),
};

export const Secondary: Story = {
  args: {
    variant: 'secondary',
  },
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Button</Button>',
  }),
};

export const Small: Story = {
  args: {
    size: 'sm',
  },
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Button</Button>',
  }),
};

export const Large: Story = {
  args: {
    size: 'lg',
  },
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Button</Button>',
  }),
};

export const Destructive: Story = {
  args: {
    variant: 'destructive',
  },
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Button</Button>',
  }),
};

export const VeryLarge: Story = {
  args: {
    size: '2xl',
  },
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Button</Button>',
  }),
};
