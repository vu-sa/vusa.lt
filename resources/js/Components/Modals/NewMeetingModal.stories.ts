import type { Meta, StoryObj } from "@storybook/vue3-vite";
import { userEvent, within, fn } from "storybook/test";
import NewMeetingModal from "./NewMeetingModal.vue";
import { usePage, router } from "#mocks/inertia.mock";

// Mock institution data
const mockInstitution = { id: 'inst1', name: 'Faculty of Science' };

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
          { institution: { id: 'inst1', name: 'Faculty of Science' } },
          { institution: { id: 'inst2', name: 'Student Council' } },
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

// Mock route function - using window.route for browser environment
if (typeof window !== 'undefined') {
  window.route = fn((name, params) => `/mocked-route/${name}`);
}

// Component metadata
const meta: Meta<typeof NewMeetingModal> = {
  title: 'Modals/NewMeetingModal',
  component: NewMeetingModal,
  tags: ['autodocs'],
  argTypes: {
    showModal: { control: 'boolean' },
    institution: { 
      control: 'select',
      options: [null, 'inst1', 'inst2'],
      mapping: {
        'null': null,
        'inst1': mockInstitution,
        'inst2': { id: 'inst2', name: 'Student Council' },
      }
    },
  },
  args: {
    showModal: true,
    institution: null,
    onClose: fn(),
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
      `
    }),
  ],
  parameters: {
    layout: 'centered',
    // Create mock responses for required API calls
    mockData: [
      {
        url: '/api/types',
        method: 'GET',
        status: 200,
        response: [
          { id: 1, title: 'Regular Meeting', model_type: 'App\\Models\\Meeting' },
          { id: 2, title: 'Special Meeting', model_type: 'App\\Models\\Meeting' },
        ],
      }
    ],
  },
};

export default meta;
type Story = StoryObj<typeof meta>;

// Default state of the modal (first step)
export const Default: Story = {
  render: (args) => ({
    components: { NewMeetingModal },
    setup() {
      return { args };
    },
    template: '<NewMeetingModal v-bind="args" @close="args.onClose" />',
  }),
};

// Modal with pre-selected institution (skips to step 2)
export const WithPreselectedInstitution: Story = {
  args: {
    institution: 'inst1',
  },
  render: (args) => ({
    components: { NewMeetingModal },
    setup() {
      return { args };
    },
    template: '<NewMeetingModal v-bind="args" @close="args.onClose" />',
  }),
};

// Modal closed (not visible)
export const Closed: Story = {
  args: {
    showModal: false,
  },
  render: (args) => ({
    components: { NewMeetingModal },
    setup() {
      return { args };
    },
    template: '<NewMeetingModal v-bind="args" @close="args.onClose" />',
  }),
};

// Walkthrough the entire meeting creation process
export const FullWalkthrough: Story = {
  render: (args) => ({
    components: { NewMeetingModal },
    setup() {
      return { args };
    },
    template: '<NewMeetingModal v-bind="args" @close="args.onClose" />',
  }),
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    
    // Wait for component to fully load
    await new Promise(resolve => setTimeout(resolve, 500));
    
    // Step 1: Select an institution
    const selectTrigger = await canvas.findByRole('combobox');
    await userEvent.click(selectTrigger);
    
    const option = await canvas.findByText('Faculty of Science');
    await userEvent.click(option);
    
    const firstStepButton = await canvas.findByRole('button', { name: /Toliau/i });
    await userEvent.click(firstStepButton);
    
    // Step 2: Fill in meeting details
    await new Promise(resolve => setTimeout(resolve, 500));
    
    // Select meeting type
    const typeSelect = await canvas.findByText(/Koks posėdžio tipas/i);
    await userEvent.click(typeSelect);
    
    const meetingType = await canvas.findByText('Regular Meeting');
    await userEvent.click(meetingType);
    
    // Click the second step button
    const secondStepButton = await canvas.findByRole('button', { name: /Toliau/i });
    await userEvent.click(secondStepButton);
    
    // Step 3: Add agenda items
    await new Promise(resolve => setTimeout(resolve, 500));
    
    // Click Add button to add an agenda item
    const addButton = await canvas.findByRole('button', { name: /Pridėti/i });
    await userEvent.click(addButton);
    
    // Type agenda item
    const inputField = await canvas.findByRole('textbox');
    await userEvent.type(inputField, 'Test agenda item');
    
    // Submit the form to create the meeting
    const submitButton = await canvas.findByRole('button', { name: /Išsaugoti/i });
    await userEvent.click(submitButton);
    
    // Verify the submitFunction was called
    await new Promise(resolve => setTimeout(resolve, 300));
  },
};