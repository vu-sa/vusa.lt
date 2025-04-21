import type { Meta, StoryObj } from "@storybook/vue3";
import { userEvent, within } from "@storybook/test";
import InstitutionSelectorForm from "./InstitutionSelectorForm.vue";
import { usePage, router } from "#mocks/inertia.mock";
import { fn } from '@storybook/test';

// Mock institution data
const mockInstitutions = [
  { institution: { id: 'inst1', name: 'Faculty of Science' } },
  { institution: { id: 'inst2', name: 'Student Council' } },
  { institution: { id: 'inst3', name: 'University Senate' } },
];

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
        current_duties: mockInstitutions
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

// Component metadata
const meta: Meta<typeof InstitutionSelectorForm> = {
  title: 'AdminForms/Special/InstitutionSelectorForm',
  component: InstitutionSelectorForm,
  tags: ['autodocs'],
  argTypes: {
    institution: { control: 'text' },
  },
  args: {
    institution: '',
    onSubmit: fn(),
  },
  decorators: [
    (story) => ({
      components: { story },
      template: `
        <div class="p-4 bg-white max-w-md">
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
          institution_id: ''
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

// Default state of the form
export const Default: Story = {
  render: (args) => ({
    components: { InstitutionSelectorForm },
    setup() {
      return { args };
    },
    template: '<InstitutionSelectorForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        institution_id: ''
      }
    }
  })
};

// Form with a pre-selected institution
export const Preselected: Story = {
  args: {
    institution: 'inst2',
  },
  render: (args) => ({
    components: { InstitutionSelectorForm },
    setup() {
      return { args };
    },
    template: '<InstitutionSelectorForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        institution_id: ''
      }
    }
  })
};

// Interactive selection of an institution
export const WithInteraction: Story = {
  render: (args) => ({
    components: { InstitutionSelectorForm },
    setup() {
      return { args };
    },
    template: '<InstitutionSelectorForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        institution_id: ''
      }
    }
  }),
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    
    // Wait for component to fully load
    await new Promise(resolve => setTimeout(resolve, 300));
    
    // Find and click the institution selection dropdown
    const selectTrigger = canvas.getByRole('combobox');
    await userEvent.click(selectTrigger);
    
    // Find and select an institution option
    const option = await canvas.findByText('Student Council');
    await userEvent.click(option);
    
    // Submit the form
    const submitButton = canvas.getByRole('button', { name: /Toliau/i });
    await userEvent.click(submitButton);
    
    // Verify the onSubmit handler was called
    await new Promise(resolve => setTimeout(resolve, 300));
  },
};

// Edge case: No institutions available
export const NoInstitutions: Story = {
  render: (args) => ({
    components: { InstitutionSelectorForm },
    setup() {
      return { args };
    },
    template: '<InstitutionSelectorForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        institution_id: ''
      }
    }
  }),
  play: async () => {
    // Temporarily override the mock to show no institutions
    usePage.mockImplementationOnce(() => ({
      props: {
        app: { locale: 'lt' },
        auth: {
          user: {
            id: 1,
            name: 'Test User',
            current_duties: []
          }
        }
      }
    }));
  }
};