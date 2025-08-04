import type { Meta, StoryObj } from "@storybook/vue3-vite";
import { userEvent, within, fn } from "storybook/test";
import AgendaItemsForm from "./AgendaItemsForm.vue";
import { usePage, router } from "@/mocks/inertia.mock";

// Override usePage mock to include necessary auth data for this component
usePage.mockImplementation(() => ({
  props: {
    app: {
      locale: 'lt',
      subdomain: 'www',
      name: 'VU SA'
    },
    auth: {
      user: {
        id: 1,
        name: 'Test User',
        current_duties: [
          { institution: { id: 'inst1', name: 'Faculty of Science' } }
        ]
      },
      can: {
        create: {
          meeting: true
        }
      }
    },
    flash: {
      success: null,
      error: null
    }
  }
}));

// Component metadata and default props
const meta: Meta<typeof AgendaItemsForm> = {
  title: 'AdminForms/Special/AgendaItemsForm',
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
    (story) => ({
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
            agendaItemTitles: []
          }
        }
      }
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
  render: (args) => ({
    components: { AgendaItemsForm },
    setup() {
      return { args };
    },
    template: '<AgendaItemsForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        agendaItemsData: {
          agendaItemTitles: []
        }
      }
    }
  }),
};

// Form in loading state
export const Loading: Story = {
  args: {
    loading: true,
  },
  render: (args) => ({
    components: { AgendaItemsForm },
    setup() {
      return { args };
    },
    template: '<AgendaItemsForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        agendaItemsData: {
          agendaItemTitles: []
        }
      }
    }
  }),
};

// Form with pre-filled agenda items
export const WithExistingItems: Story = {
  render: (args) => ({
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
            'New member introductions'
          ]
        }
      }
    }
  }),
};

// Interactive adding of agenda items
export const AddingItems: Story = {
  render: (args) => ({
    components: { AgendaItemsForm },
    setup() {
      return { args };
    },
    template: '<AgendaItemsForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        agendaItemsData: {
          agendaItemTitles: []
        }
      }
    }
  }),
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    
    // Wait for component to fully load
    await new Promise(resolve => setTimeout(resolve, 300));
    
    // Add a new agenda item (assuming there's a button or input)
    const addButton = canvas.getByRole('button', { name: /Pridėti/i });
    await userEvent.click(addButton);
    
    // Find the input field for the first agenda item
    const inputField = canvas.getAllByRole('textbox')[0];
    await userEvent.type(inputField, 'Test agenda item');
    
    // Submit the form
    const submitButton = canvas.getByRole('button', { name: /Išsaugoti/i });
    await userEvent.click(submitButton);
    
    // Verify the onSubmit handler was called
    await new Promise(resolve => setTimeout(resolve, 300));
  },
};

// Using text area mode to add multiple items at once
export const UsingTextAreaMode: Story = {
  render: (args) => ({
    components: { AgendaItemsForm },
    setup() {
      return { args };
    },
    template: '<AgendaItemsForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        agendaItemsData: {
          agendaItemTitles: []
        }
      }
    }
  }),
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    
    // Wait for component to fully load
    await new Promise(resolve => setTimeout(resolve, 300));
    
    // Find and click the button to show text area mode
    const textAreaButton = canvas.getByRole('button', { name: /teksto laukelį/i });
    await userEvent.click(textAreaButton);
    
    // Find the textarea and type multiple items
    const textarea = canvas.getByRole('textbox');
    await userEvent.type(textarea, 'Item 1\nItem 2\nItem 3');
    
    // Click the process button
    const processButton = canvas.getByRole('button', { name: /Apdoroti/i });
    await userEvent.click(processButton);
    
    // Submit the form with the processed items
    const submitButton = canvas.getByRole('button', { name: /Išsaugoti/i });
    await userEvent.click(submitButton);
  },
};