import type { Meta, StoryObj } from '@storybook/vue3';

import ModelChip from "./ModelChip.vue";
import DeviceMeetingRoomRemote24Regular from "~icons/fluent/device-meeting-room-remote24-regular";

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta: Meta<typeof ModelChip> = {
  title: 'Vusa/ModelChip',
  component: ModelChip,
  tags: ['autodocs'],
};

export default meta;

type Story = StoryObj<typeof meta>;

export const Meetings: Story = {
  render: (args) => ({
    components: { ModelChip, DeviceMeetingRoomRemote24Regular },
    setup() {
      return { args };
    },
    template: `
      <ModelChip v-bind="args">
        <template #icon>
          <DeviceMeetingRoomRemote24Regular />
        </template>
        Susitikimas
      </ModelChip>
    `,
  }),
};
