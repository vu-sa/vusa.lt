import type { Meta, StoryObj } from '@storybook/vue3-vite';

import LoginForm from './LoginForm.vue';

import { usePage } from '@/mocks/inertia.storybook';

const meta: Meta<typeof LoginForm> = {
  title: 'Pages/Admin/LoginForm',
  component: LoginForm,
  globals: { surface: 'admin' },
  parameters: {
    layout: 'fullscreen',
  },
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: () => ({
    components: { LoginForm },
    setup() {
      usePage.mockImplementation(() => ({
        props: {
          app: { locale: 'lt', subdomain: 'www' },
          errors: {},
          organization: { privacyPageUrl: '#' },
        },
      }));
      return {};
    },
    template: '<LoginForm />',
  }),
};

export const WithErrors: Story = {
  render: () => ({
    components: { LoginForm },
    setup() {
      usePage.mockImplementation(() => ({
        props: {
          app: { locale: 'lt', subdomain: 'www' },
          errors: {
            email: 'Šie prisijungimo duomenys neatitinka mūsų įrašų.',
          },
          organization: { privacyPageUrl: '#' },
        },
      }));
      return {};
    },
    template: '<LoginForm />',
  }),
};

export const WithStatus: Story = {
  render: () => ({
    components: { LoginForm },
    setup() {
      usePage.mockImplementation(() => ({
        props: {
          app: { locale: 'lt', subdomain: 'www' },
          errors: {},
          organization: { privacyPageUrl: '#' },
        },
      }));
      return {};
    },
    template: '<LoginForm status="Prisijungimo sesija atnaujinta." />',
  }),
};
