import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { fn } from 'storybook/test';
import Button from './Button.vue';

const meta: Meta<typeof Button> = {
  title: 'UI/Button',
  component: Button,
  tags: ['autodocs'],
  argTypes: {
    variant: { 
      control: 'select', 
      options: ['default', 'destructive', 'outline', 'secondary', 'ghost', 'link'] 
    },
    size: { 
      control: 'select', 
      options: ['default', 'sm', 'lg', 'icon'] 
    },
  },
  args: {
    variant: 'default',
    size: 'default',
    onClick: fn(),
  },
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Click me</Button>',
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
    template: '<Button v-bind="args">Secondary</Button>',
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
    template: '<Button v-bind="args">Delete</Button>',
  }),
};