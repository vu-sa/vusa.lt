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
      options: ['default', 'destructive', 'outline', 'secondary', 'ghost', 'link'],
      description: 'The visual style variant of the button',
      table: {
        defaultValue: { summary: 'default' },
      },
    },
    size: {
      control: 'select',
      options: ['default', 'sm', 'lg', 'icon'],
      description: 'The size of the button',
      table: {
        defaultValue: { summary: 'default' },
      },
    },
    animation: {
      control: 'select',
      options: ['none', 'subtle', 'bounce'],
      description: 'Optional animation effect on hover/active',
      table: {
        defaultValue: { summary: 'none' },
      },
    },
    disabled: {
      control: 'boolean',
      description: 'Whether the button is disabled',
    },
    onClick: {
      action: 'clicked',
      description: 'Emitted when the button is clicked',
    },
  },
  args: {
    variant: 'default',
    size: 'default',
    onClick: fn(),
  },
  parameters: {
    docs: {
      description: {
        component: `A versatile button component with multiple variants and sizes. Based on shadcn/ui Button.

## Features
- Multiple visual variants (default, secondary, destructive, outline, ghost, link)
- Multiple sizes (default, sm, lg, icon)
- Optional animation effects for interactive feedback
- Full accessibility support
- Can render as any HTML element or custom component via \`as\` prop`,
      },
    },
  },
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: args => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args" @click="args.onClick">Click me</Button>',
  }),
};

export const Secondary: Story = {
  args: {
    variant: 'secondary',
  },
  render: args => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args" @click="args.onClick">Secondary</Button>',
  }),
};

export const Destructive: Story = {
  args: {
    variant: 'destructive',
  },
  render: args => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args" @click="args.onClick">Delete</Button>',
  }),
};

export const Outline: Story = {
  args: {
    variant: 'outline',
  },
  render: args => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args" @click="args.onClick">Outline</Button>',
  }),
};

export const Ghost: Story = {
  args: {
    variant: 'ghost',
  },
  render: args => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args" @click="args.onClick">Ghost</Button>',
  }),
};

export const Link: Story = {
  args: {
    variant: 'link',
  },
  render: args => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args" @click="args.onClick">Link Style</Button>',
  }),
};

export const Small: Story = {
  args: {
    size: 'sm',
  },
  render: args => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args" @click="args.onClick">Small Button</Button>',
  }),
};

export const Large: Story = {
  args: {
    size: 'lg',
  },
  render: args => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args" @click="args.onClick">Large Button</Button>',
  }),
};

export const Icon: Story = {
  args: {
    size: 'icon',
    variant: 'outline',
  },
  render: args => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args" @click="args.onClick"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></Button>',
  }),
};

export const Disabled: Story = {
  args: {
    disabled: true,
  },
  render: args => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args" @click="args.onClick">Disabled</Button>',
  }),
  parameters: {
    docs: {
      description: {
        story: 'A disabled button that cannot be clicked.',
      },
    },
  },
};

export const AllVariants: Story = {
  render: () => ({
    components: { Button },
    template: `
      <div class="flex flex-wrap gap-4">
        <Button variant="default">Default</Button>
        <Button variant="secondary">Secondary</Button>
        <Button variant="destructive">Destructive</Button>
        <Button variant="outline">Outline</Button>
        <Button variant="ghost">Ghost</Button>
        <Button variant="link">Link</Button>
      </div>
    `,
  }),
  parameters: {
    docs: {
      description: {
        story: 'All available button variants displayed together for comparison.',
      },
    },
  },
};

export const AllSizes: Story = {
  render: () => ({
    components: { Button },
    template: `
      <div class="flex items-center gap-4">
        <Button size="sm">Small</Button>
        <Button size="default">Default</Button>
        <Button size="lg">Large</Button>
        <Button size="icon" variant="outline">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
        </Button>
      </div>
    `,
  }),
  parameters: {
    docs: {
      description: {
        story: 'All available button sizes displayed together for comparison.',
      },
    },
  },
};

export const WithAnimation: Story = {
  args: {
    animation: 'subtle',
  },
  render: args => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args" @click="args.onClick">Hover me</Button>',
  }),
  parameters: {
    docs: {
      description: {
        story: 'Button with subtle animation on hover. Try hovering over the button to see the effect.',
      },
    },
  },
};

export const AllAnimations: Story = {
  render: () => ({
    components: { Button },
    template: `
      <div class="flex items-center gap-4">
        <Button animation="none">No Animation</Button>
        <Button animation="subtle">Subtle</Button>
        <Button animation="bounce">Bounce</Button>
      </div>
    `,
  }),
  parameters: {
    docs: {
      description: {
        story: 'All available animation variants. Hover over each button to see the effect.',
      },
    },
  },
};
