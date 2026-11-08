import type { Meta, StoryObj } from '@storybook/vue3-vite';

import MeetingForm from './MeetingForm.vue';

import { MeetingKind, MeetingType } from '@/Types/enums';

const meeting: InstanceType<typeof MeetingForm>['$props']['meeting'] = {
  id: '01K5K4HMGJMV7A1TX15BQVH4PC',
  start_time: '2026-09-28T14:30:00',
  type: MeetingType.InPerson,
  meeting_kind: null,
  inherited_meeting_kind: MeetingKind.Assembly,
  created_at: '2026-09-20T09:00:00.000Z',
  updated_at: '2026-09-20T09:00:00.000Z',
  description: { lt: 'Atstovų susitikimas', en: 'Representatives meeting' },
};

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
      type: MeetingType.Email,
    },
  },
};
