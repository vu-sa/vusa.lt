import type { Meta, StoryObj } from '@storybook/vue3-vite';

import CollectionSkeleton from './Skeletons/CollectionSkeleton.vue';
import RecordSkeleton from './Skeletons/RecordSkeleton.vue';
import FormSkeleton from './Skeletons/FormSkeleton.vue';
import SectionCardSkeleton from './Skeletons/SectionCardSkeleton.vue';

const meta: Meta = {
  title: 'Patterns/Skeletons',
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' } },
};

export default meta;

export const CollectionRows: StoryObj = {
  render: () => ({
    components: { CollectionSkeleton },
    template: `
      <div class="p-6 bg-background text-foreground max-w-4xl mx-auto">
        <h2 class="text-xs uppercase font-medium tracking-wide text-muted-foreground mb-4">Kolekcijos eilučių vaizdo griaučiai</h2>
        <CollectionSkeleton view-mode="rows" :rows="5" />
      </div>
    `,
  }),
};

export const CollectionTable: StoryObj = {
  render: () => ({
    components: { CollectionSkeleton },
    template: `
      <div class="p-6 bg-background text-foreground max-w-4xl mx-auto">
        <h2 class="text-xs uppercase font-medium tracking-wide text-muted-foreground mb-4">Kolekcijos lentelės vaizdo griaučiai</h2>
        <CollectionSkeleton view-mode="table" :rows="5" />
      </div>
    `,
  }),
};

export const RecordPage: StoryObj = {
  render: () => ({
    components: { RecordSkeleton },
    template: `
      <div class="p-6 bg-background text-foreground max-w-4xl mx-auto">
        <h2 class="text-xs uppercase font-medium tracking-wide text-muted-foreground mb-4">Įrašo puslapio griaučiai</h2>
        <RecordSkeleton />
      </div>
    `,
  }),
};

export const FormPage: StoryObj = {
  render: () => ({
    components: { FormSkeleton },
    template: `
      <div class="p-6 bg-background text-foreground max-w-4xl mx-auto">
        <h2 class="text-xs uppercase font-medium tracking-wide text-muted-foreground mb-4">Formos puslapio griaučiai</h2>
        <FormSkeleton :fields="4" />
      </div>
    `,
  }),
};

export const SectionCardPanel: StoryObj = {
  render: () => ({
    components: { SectionCardSkeleton },
    template: `
      <div class="p-6 bg-background text-foreground max-w-2xl mx-auto">
        <h2 class="text-xs uppercase font-medium tracking-wide text-muted-foreground mb-4">Skilties skydelio griaučiai</h2>
        <SectionCardSkeleton :items="3" />
      </div>
    `,
  }),
};
