import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { expect, userEvent, within, fn } from 'storybook/test';

import MeetingForm from './MeetingForm.vue';

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
          { institution: { id: 'inst2', name: 'Student Council' } },
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

// Mock route function for browser environment
if (typeof window !== 'undefined') {
  window.route = fn((name, params) => `/mocked-route/${name}`);
}

// Component metadata including default args and controls
const meta: Meta<typeof MeetingForm> = {
  title: 'Forms/AdminForms/MeetingForm',
  component: MeetingForm,
  tags: ['autodocs'],
  argTypes: {
    loading: { control: 'boolean' },
    meeting: { control: 'object' },
  },
  args: {
    loading: false,
    meeting: {
      start_time: '2025-04-20T10:00:00',
      type_id: 1,
    },
    meetingTypes: [
      { id: 1, title: 'Regular Meeting', model_type: 'meeting' },
      { id: 2, title: 'Special Meeting', model_type: 'meeting' },
      { id: 3, title: 'Emergency Meeting', model_type: 'meeting' },
    ],
    onSubmit: fn(),
  },
  decorators: [
    story => ({
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
          meetingData: {},
        },
      },
    }),
  ],
  parameters: {
    layout: 'centered',
    // Mock fetch response for meeting types
    mockData: [
      {
        url: '/api/types',
        method: 'GET',
        status: 200,
        response: [
          { id: 1, title: 'Regular Meeting', model_type: 'meeting' },
          { id: 2, title: 'Special Meeting', model_type: 'meeting' },
          { id: 3, title: 'Emergency Meeting', model_type: 'meeting' },
        ],
      },
    ],
  },
};

export default meta;
type Story = StoryObj<typeof meta>;

// Default state of the form
export const Default: Story = {
  render: args => ({
    components: { MeetingForm },
    setup() {
      return { args };
    },
    template: '<MeetingForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        meetingData: {},
      },
    },
  }),
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    // Wait for the component to be fully loaded after Suspense resolves
    await new Promise(resolve => setTimeout(resolve, 500));
  },
};

// Form with loading state
export const Loading: Story = {
  args: {
    loading: true,
  },
  render: args => ({
    components: { MeetingForm },
    setup() {
      return { args };
    },
    template: '<MeetingForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        meetingData: {},
      },
    },
  }),
};

// Form with pre-filled meeting data
export const Prefilled: Story = {
  args: {
    meeting: {
      start_time: '2025-05-15T14:30:00',
      type_id: 2,
    },
  },
  render: args => ({
    components: { MeetingForm },
    setup() {
      return { args };
    },
    template: '<MeetingForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        meetingData: {},
      },
    },
  }),
};

// Form with validation errors - Note: Complex async interactions may need manual testing
export const WithInteraction: Story = {
  render: args => ({
    components: { MeetingForm },
    setup() {
      return { args };
    },
    template: '<MeetingForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        meetingData: {},
      },
    },
  }),
  // The meeting type is a radio group, not a dropdown — picking one is a single click, so
  // this asserts the selection actually lands rather than only that a menu opened.
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);

    const remote = await canvas.findByRole('radio', { name: /Nuotolinis/ });
    await userEvent.click(remote);

    await expect(remote).toBeChecked();
  },
  parameters: {
    docs: {
      description: {
        story: 'Interactive form demonstration: picking a meeting type.',
      },
    },
  },
};
