import type { Meta, StoryObj } from '@storybook/vue3';

import RCFlowGraph from './RCFlowGraph.vue';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta: Meta<typeof RCFlowGraph> = {
    title: 'Vusa/RC/FlowGraph',
    component: RCFlowGraph,
    tags: ['autodocs'],
    args: {
        element: {
            json_content: {
                preset: 'VusaStructure'
            },
            options: null
        },
    }
};

export default meta;

type Story = StoryObj<typeof meta>;

export const VUSR: Story = {
    render: (args) => ({
        components: { RCFlowGraph },
        setup() {
            return { args };
        },
        template: `
      <RCFlowGraph v-bind="args" />
    `,
    }),
};
