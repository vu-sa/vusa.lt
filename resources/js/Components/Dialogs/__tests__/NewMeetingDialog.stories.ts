import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { fn } from 'storybook/test';

import NewMeetingDialog from '../NewMeetingDialog.vue';

import { usePage } from '@/mocks/inertia.storybook';
import { createMockAuthUser, getMockTenantsList } from '@/mocks/fixtures';

// Mock institution data for accessible institutions
const accessibleInstitutions = [
  {
    id: 'inst1',
    name: 'Faculty of Science',
    shortname: 'FS',
    tenant: { id: 'vusa', shortname: 'VU SA' },
    last_meeting_date: '2024-01-15',
    active_check_in: false,
    meetings: [],
  },
  {
    id: 'inst2',
    name: 'Student Council',
    shortname: 'SC',
    tenant: { id: 'vusa', shortname: 'VU SA' },
    last_meeting_date: '2024-01-10',
    active_check_in: false,
    meetings: [],
  },
  {
    id: 'inst3',
    name: 'University Senate',
    shortname: 'US',
    tenant: { id: 'vusa', shortname: 'VU SA' },
    last_meeting_date: null,
    active_check_in: false,
    meetings: [],
  },
];

// Override usePage mock to use shared fixtures
usePage.mockImplementation(() => ({
  props: {
    app: {
      locale: 'lt',
      subdomain: 'www',
      name: 'VU SA',
      url: 'http://localhost',
    },
    auth: {
      user: {
        ...createMockAuthUser(),
        current_duties: [
          {
            institution: {
              id: 'inst1',
              name: 'Faculty of Science',
              shortname: 'FS',
              types: [{ title: 'Faculty' }],
              last_meeting_date: '2024-01-15',
              active_check_in: false,
              meetings: [],
            },
          },
          {
            institution: {
              id: 'inst2',
              name: 'Student Council',
              shortname: 'SC',
              types: [{ title: 'Council' }],
              last_meeting_date: '2024-01-10',
              active_check_in: false,
              meetings: [],
            },
          },
        ],
      },
      can: {
        create: {
          meeting: true,
          document: true,
        },
      },
    },
    accessibleInstitutions,
    tenants: getMockTenantsList() as any,
    flash: {
      success: null,
      error: null,
      info: null,
      warning: null,
    },
  },
}));

// Mock institution data using shared fixtures pattern
const mockInstitution = { id: 'inst1', name: 'Faculty of Science' };

// Component metadata
const meta: Meta<typeof NewMeetingDialog> = {
  title: 'Components/Dialogs/NewMeetingDialog',
  component: NewMeetingDialog,
  tags: ['autodocs'],
  argTypes: {
    showModal: { control: 'boolean' },
    institution: {
      control: 'select',
      options: [null, 'inst1', 'inst2'],
      mapping: {
        null: null,
        inst1: mockInstitution,
        inst2: { id: 'inst2', name: 'Student Council' },
      },
    },
  },
  args: {
    showModal: true,
    institution: null,
    asDialog: false, // Render inline without dialog overlay for Storybook
    onClose: fn(),
  },
  decorators: [
    story => ({
      components: { story },
      template: `
        <div class="p-4 bg-zinc-100 min-h-screen">
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
    }),
  ],
  parameters: {
    layout: 'fullscreen',
    backgrounds: { default: 'light' },
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
      },
    ],
  },
};

export default meta;
type Story = StoryObj<typeof meta>;

// Default state of the dialog (first step)
export const Default: Story = {
  render: args => ({
    components: { NewMeetingDialog },
    setup() {
      return { args };
    },
    template: '<NewMeetingDialog v-bind="args" @close="args.onClose" />',
  }),
};

// Dialog with pre-selected institution (skips to step 2)
export const WithPreselectedInstitution: Story = {
  args: {
    institution: 'inst1',
  },
  render: args => ({
    components: { NewMeetingDialog },
    setup() {
      return { args };
    },
    template: '<NewMeetingDialog v-bind="args" @close="args.onClose" />',
  }),
};

// Dialog closed (not visible)
export const Closed: Story = {
  args: {
    showModal: false,
  },
  render: args => ({
    components: { NewMeetingDialog },
    setup() {
      return { args };
    },
    template: '<NewMeetingDialog v-bind="args" @close="args.onClose" />',
  }),
};

// Walkthrough the entire meeting creation process
export const FullWalkthrough: Story = {
  render: args => ({
    components: { NewMeetingDialog },
    setup() {
      return { args };
    },
    template: '<NewMeetingDialog v-bind="args" @close="args.onClose" />',
  }),
  parameters: {
    docs: {
      description: {
        story: 'This story showcases the full meeting creation process. Complex multi-step interactions may require manual testing.',
      },
    },
  },
};
