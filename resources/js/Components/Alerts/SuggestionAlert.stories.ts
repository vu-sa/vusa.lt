import type { Meta, StoryObj } from '@storybook/vue3-vite';
import SuggestionAlert from './SuggestionAlert.vue';

// Import the mock for laravel-vue-i18n
import { trans } from '@/mocks/i18n.mock';

const meta: Meta<typeof SuggestionAlert> = {
  title: 'Alerts/SuggestionAlert',
  component: SuggestionAlert,
  argTypes: {
    modelValue: { control: 'boolean' },
  },
  args: {
    modelValue: true,
  },
  parameters: {
    docs: {
      description: {
        component: 'A suggestion alert component used to display helpful tips and guidance to users throughout the application.'
      }
    }
  }
};

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: (args) => ({
    components: { SuggestionAlert },
    setup() {
      // Configure mock behavior for this story
      trans.mockImplementation((key) => {
        // Add translations for keys used in this component
        const translations: Record<string, string> = {
          'Įsidėmėk': 'Remember',
        };
        return translations[key] || key;
      });
      
      return { args };
    },
    template: '<SuggestionAlert v-bind="args">This is a suggestion alert with important information for the user. It can contain <strong>rich text</strong> and <ul><li>list items</li><li>for better readability</li></ul></SuggestionAlert>',
  }),
  parameters: {
    docs: {
      description: {
        story: 'The default state of the suggestion alert with rich content inside.'
      }
    },
    a11y: {
      config: {
        rules: [
          { id: 'color-contrast', enabled: true },
          { id: 'aria-roles', enabled: true }
        ]
      }
    }
  }
};

export const Hidden: Story = {
  args: {
    modelValue: false
  },
  render: (args) => ({
    components: { SuggestionAlert },
    setup() {
      return { args };
    },
    template: '<SuggestionAlert v-bind="args">This alert will not be visible initially.</SuggestionAlert>',
  }),
  parameters: {
    docs: {
      description: {
        story: 'When modelValue is set to false, the alert is not displayed.'
      }
    }
  }
};

export const WithLongContent: Story = {
  render: (args) => ({
    components: { SuggestionAlert },
    setup() {
      return { args };
    },
    template: `<SuggestionAlert v-bind="args">
      <p>This alert contains longer content to demonstrate how the component handles more extensive information.</p>
      <p>Multiple paragraphs can be included when needed to properly explain complex concepts or provide detailed instructions.</p>
      <ul>
        <li>The first important point to remember</li>
        <li>Another key consideration for the user</li>
        <li>A final reminder about this functionality</li>
      </ul>
    </SuggestionAlert>`,
  }),
  parameters: {
    docs: {
      description: {
        story: 'Demonstrates how the alert handles longer content with multiple paragraphs and lists.'
      }
    }
  }
};