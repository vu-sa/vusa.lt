import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { userEvent, within, fn } from 'storybook/test';

import InstitutionSelectorForm from './InstitutionSelectorForm.vue';

import { usePage, router } from '@/mocks/inertia.storybook';

// Mock institution data
const mockInstitutions = [
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
  {
    institution: {
      id: 'inst3',
      name: 'University Senate',
      shortname: 'US',
      types: [{ title: 'Senate' }],
      last_meeting_date: null,
      active_check_in: false,
      meetings: [],
    },
  },
];

// Accessible institutions for admin search
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
  {
    id: 'inst4',
    name: 'Research Committee',
    shortname: 'RC',
    tenant: { id: 'vusa', shortname: 'VU SA' },
    last_meeting_date: '2024-01-05',
    active_check_in: false,
    meetings: [{ id: 1 }],
  },
];

// Override usePage mock to include necessary auth data for this component
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
        id: 1,
        name: 'Test User',
        current_duties: mockInstitutions,
      },
      can: {
        create: {
          meeting: true,
          document: true,
        },
      },
    },
    accessibleInstitutions,
    flash: {
      success: null,
      error: null,
      info: null,
      warning: null,
    },
  },
}));

// Component metadata
const meta: Meta<typeof InstitutionSelectorForm> = {
  title: 'Forms/AdminForms/Special/InstitutionSelectorForm',
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
          institution_id: '',
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

// Default state of the form
export const Default: Story = {
  render: args => ({
    components: { InstitutionSelectorForm },
    setup() {
      return { args };
    },
    template: '<InstitutionSelectorForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        institution_id: '',
      },
    },
  }),
};

// Form with a pre-selected institution
export const Preselected: Story = {
  args: {
    institution: 'inst2',
  },
  render: args => ({
    components: { InstitutionSelectorForm },
    setup() {
      return { args };
    },
    template: '<InstitutionSelectorForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        institution_id: '',
      },
    },
  }),
};

// Interactive selection of an institution
export const WithInteraction: Story = {
  render: args => ({
    components: { InstitutionSelectorForm },
    setup() {
      return { args };
    },
    template: '<InstitutionSelectorForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        institution_id: '',
      },
    },
  }),
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);

    // Wait for component to fully load
    await new Promise(resolve => setTimeout(resolve, 300));

    // Find and click a specific institution button (exact match to avoid multiple matches)
    const institutionButton = await canvas.findByRole('button', { name: /VU SA Fakulteto taryba/i });
    await userEvent.click(institutionButton);

    // Wait for selection to be processed
    await new Promise(resolve => setTimeout(resolve, 300));
  },
};

// Edge case: No institutions available
export const NoInstitutions: Story = {
  render: args => ({
    components: { InstitutionSelectorForm },
    setup() {
      return { args };
    },
    template: '<InstitutionSelectorForm v-bind="args" @submit="args.onSubmit" />',
    provide: {
      meetingFormState: {
        institution_id: '',
      },
    },
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
            current_duties: [],
          },
        },
      },
    }));
  },
};
