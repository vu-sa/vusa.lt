import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { userEvent, within, fn } from 'storybook/test';

import AgendaItemsForm from './AgendaItemsForm.vue';

import { usePage, router } from '@/mocks/inertia.storybook';

// Override usePage mock to include necessary auth data for this component
usePage.mockImplementation(() => ({
  props: {
    app: {
      locale: 'lt',
      subdomain: 'www',
      name: 'VU SA',
    },
    auth: {
      user: {
        id: 1,
        name: 'Test User',
        current_duties: [
          { institution: { id: 'inst1', name: 'Faculty of Science' } },
        ],
      },
      can: {
        create: {
          meeting: true,
        },
      },
    },
    flash: {
      success: null,
      error: null,
    },
  },
}));

// Component metadata and default props
const meta: Meta<typeof AgendaItemsForm> = {
  title: 'Forms/AdminForms/Special/AgendaItemsForm',
  component: AgendaItemsForm,
  tags: ['autodocs'],
  argTypes: {
    loading: { control: 'boolean' },
  },
  args: {
    loading: false,
    onSubmit: fn(),
  },
  decorators: [
    story => ({
      components: { story },
      template: `
        <div class="p-4 bg-white max-w-xl">
          <Suspense>
            <story />
            <template #fallback>
              <div class="flex items-center justify-center p-4">
                <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
              </div>
            </template>
          </Suspense>
        </div>
      `,
      provide: {
        meetingFormState: {
          agendaItemsData: {
            agendaItemTitles: [],
          },
        },
      },
    }),
  ],
  parameters: {
    layout: 'centered',
  },
};

export default meta;
type Story = StoryObj<typeof meta>;

// Default empty form
export const Default: Story = {
  render: args => ({
    components: { AgendaItemsForm },
    setup() {
      return { args };
    },
    template: '<AgendaItemsForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        agendaItemsData: {
          agendaItemTitles: [],
        },
      },
    },
  }),
};

// Form in loading state
export const Loading: Story = {
  args: {
    loading: true,
  },
  render: args => ({
    components: { AgendaItemsForm },
    setup() {
      return { args };
    },
    template: '<AgendaItemsForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        agendaItemsData: {
          agendaItemTitles: [],
        },
      },
    },
  }),
};

// Form with pre-filled agenda items
export const WithExistingItems: Story = {
  render: args => ({
    components: { AgendaItemsForm },
    setup() {
      return { args };
    },
    template: '<AgendaItemsForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        agendaItemsData: {
          agendaItemTitles: [
            'Discuss last meeting action items',
            'Budget review for next quarter',
            'New member introductions',
          ],
        },
      },
    },
  }),
};

// Interactive adding of agenda items
export const AddingItems: Story = {
  render: args => ({
    components: { AgendaItemsForm },
    setup() {
      return { args };
    },
    template: '<AgendaItemsForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        agendaItemsData: {
          agendaItemTitles: [],
        },
      },
    },
  }),
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);

    // Wait for component to fully load
    await new Promise(resolve => setTimeout(resolve, 300));

    // Click the "Add one by one" option from the 3-button selection screen
    const addOneByOneButton = await canvas.findByText('Pridėti po vieną');
    await userEvent.click(addOneByOneButton);

    // Wait for the form to update and show the textarea
    await new Promise(resolve => setTimeout(resolve, 300));

    // Find the textarea for the first agenda item and type
    const inputField = canvas.getAllByRole('textbox')[0];
    await userEvent.type(inputField, 'Test agenda item');

    // Wait a bit for validation
    await new Promise(resolve => setTimeout(resolve, 100));
  },
};

// Using text area mode to add multiple items at once
export const UsingTextAreaMode: Story = {
  render: args => ({
    components: { AgendaItemsForm },
    setup() {
      return { args };
    },
    template: '<AgendaItemsForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        agendaItemsData: {
          agendaItemTitles: [],
        },
      },
    },
  }),
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);

    // Wait for component to fully load
    await new Promise(resolve => setTimeout(resolve, 300));

    // Click the "Add one by one" option from the 3-button selection screen
    const addOneByOneButton = await canvas.findByText('Pridėti po vieną');
    await userEvent.click(addOneByOneButton);

    // Wait for the form to update
    await new Promise(resolve => setTimeout(resolve, 300));
  },
};
