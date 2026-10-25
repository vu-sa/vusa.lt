import type { Meta, StoryObj } from '@storybook/vue3-vite';

import PreviewChipList from './PreviewChipList.vue';
import PreviewFact from './PreviewFact.vue';
import PreviewFacts from './PreviewFacts.vue';
import PreviewPane from './PreviewPane.vue';
import PreviewSection from './PreviewSection.vue';
import StatusBadge from './StatusBadge.vue';

import { Button } from '@/Components/ui/button';
import { meetingCompletionStatuses } from '@/Constants/statuses';

const meta: Meta<typeof PreviewPane> = {
  title: 'Patterns/Preview',
  component: PreviewPane,
  tags: ['autodocs'],
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' } },
};

export default meta;
type Story = StoryObj<typeof meta>;

const institutions = Array.from({ length: 9 }, (_, i) => ({ id: i, name: `Fakulteto taryba ${i + 1}`, href: '#', meta: 'MIF' }));

export const Record: Story = {
  render: () => ({
    components: { Button, PreviewChipList, PreviewFact, PreviewFacts, PreviewPane, PreviewSection, StatusBadge },
    setup: () => ({ institutions, status: Object.values(meetingCompletionStatuses)[0] }),
    template: `
      <div class="max-w-xl border border-border bg-background text-foreground">
        <PreviewPane eyebrow="Posėdis" entity-type="meeting" title="Senato posėdis" href="#" subtitle="VU Senatas">
          <template #badges><StatusBadge :status /></template>
          <template #actions>
            <Button variant="brand" size="sm">Atidaryti</Button>
            <Button variant="outline" size="sm">Redaguoti</Button>
          </template>
          <PreviewFacts>
            <PreviewFact label="Data" value="2026-10-25 14:00" tabular />
            <PreviewFact label="Punktai" :value="0" tone="attention" />
            <PreviewFact label="Padalinys" />
          </PreviewFacts>
          <PreviewSection title="Susijusios institucijos" :count="institutions.length">
            <PreviewChipList :items="institutions" :max="6" more-href="#" />
          </PreviewSection>
          <PreviewSection title="Darbotvarkė" loading />
          <PreviewSection title="Atstovai" empty empty-message="Atstovų nėra" />
        </PreviewPane>
      </div>
    `,
  }),
};
