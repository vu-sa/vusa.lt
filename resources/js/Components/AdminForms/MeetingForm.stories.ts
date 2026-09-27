import type { Meta, StoryObj } from '@storybook/vue3-vite';

import MeetingForm from './MeetingForm.vue';

const meeting = {
  id: 1,
  start_time: '2026-09-28T14:30:00',
  type: 'in-person',
  description: { lt: 'Atstovų susitikimas', en: 'Representatives meeting' },
} as App.Entities.Meeting;

const meta: Meta<typeof MeetingForm> = {
  title: 'Forms/AdminForms/MeetingForm',
  component: MeetingForm,
  tags: ['autodocs'],
  globals: { surface: 'admin' },
  parameters: { layout: 'fullscreen' },
  args: { open: true, meeting },
};

export default meta;
type Story = StoryObj<typeof meta>;

export const InPerson: Story = {};

export const EmailMeeting: Story = {
  args: {
    meeting: {
      ...meeting,
      type: 'email',
    },
  },
};
