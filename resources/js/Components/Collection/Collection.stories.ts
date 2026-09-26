import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { ref } from 'vue';

import CollectionActiveChips from './CollectionActiveChips.vue';
import CollectionFilterBar from './CollectionFilterBar.vue';
import CollectionQuickFilters from './CollectionQuickFilters.vue';
import CollectionTitleBand from './CollectionTitleBand.vue';
import CollectionViewToggle from './CollectionViewToggle.vue';

import MeetingCollectionRow from '@/Components/Meetings/MeetingCollectionRow.vue';
import { CollectionSkeleton, EmptyState } from '@/Components/Patterns';
import type { CollectionFacet } from '@/Composables/useCollectionSource';

const meta: Meta = {
  title: 'Collection/Anatomy',
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' } },
};

export default meta;
type Story = StoryObj;

const facets: CollectionFacet[] = [
  {
    field: 'year',
    label: 'Metai',
    type: 'year-pills',
    values: [
      { value: '2026', label: '2026', count: 41, isSelected: true },
      { value: '2025', label: '2025', count: 96, isSelected: false },
    ],
  },
  {
    field: 'completion_status',
    label: 'Būsena',
    type: 'checkbox',
    values: [
      { value: 'complete', label: 'Užpildyta', count: 30, isSelected: false },
      { value: 'incomplete', label: 'Neužpildyta', count: 9, isSelected: true },
      { value: 'no_items', label: 'Nėra darbotvarkės', count: 2, isSelected: false },
    ],
  },
];

const at = (iso: string) => Date.parse(iso) / 1000;

const meetings = [
  { id: '1', title: 'Senato posėdis', start_time: at('2026-09-18T07:00:00Z'), institution_name_lt: 'VU SA Senato atstovai', tenant_shortname: 'VU SA', agenda_items_count: 6, type: 'in-person', completion_status: 'incomplete' },
  { id: '2', title: 'Sprendimas dėl biudžeto', start_time: at('2026-09-12T20:59:00Z'), institution_name_lt: 'VU MIF studentų atstovybė', tenant_shortname: 'VU MIF', agenda_items_count: 1, type: 'email', completion_status: 'complete' },
  { id: '3', title: 'Tarybos posėdis', start_time: at('2026-09-03T15:00:00Z'), institution_name_lt: 'VU SA Taryba', tenant_shortname: 'VU SA', agenda_items_count: 0, type: 'remote', completion_status: 'no_items' },
];

export const TitleBand: Story = {
  render: () => ({
    components: { CollectionTitleBand },
    template: `
      <div class="max-w-5xl bg-background p-6 text-foreground">
        <CollectionTitleBand eyebrow="ViSAK · Posėdžiai" title="Posėdžiai" entity-type="meeting"
          lead="Fiksuok posėdžius, jų darbotvarkę ir balsavimus vienoje vietoje." />
      </div>`,
  }),
};

export const QuickFiltersAndViews: Story = {
  render: () => ({
    components: { CollectionQuickFilters, CollectionViewToggle },
    setup: () => ({
      view: ref('rows'),
      quick: [
        { id: 'mine', label: 'Mano institucijos', active: true },
        { id: 'no_votes', label: 'Be balsavimų', active: false },
        { id: 'this_year', label: 'Šie metai', active: false },
      ],
    }),
    template: `
      <div class="flex flex-col gap-4 bg-background p-6 text-foreground">
        <CollectionQuickFilters :filters="quick" />
        <CollectionViewToggle v-model="view" :views="['rows', 'table', 'preview']" />
      </div>`,
  }),
};

export const FilterBarOpen: Story = {
  render: () => ({
    components: { CollectionFilterBar },
    setup: () => ({ facets, sheetOpen: ref(false) }),
    template: `
      <div class="w-[880px] bg-background p-6 text-foreground">
        <CollectionFilterBar v-model:sheet-open="sheetOpen" :facets="facets" open :is-at-least-md="true" :active-count="2" />
      </div>`,
  }),
};

export const ActiveChips: Story = {
  render: () => ({
    components: { CollectionActiveChips },
    setup: () => ({ chips: [{ id: 'year:2026', label: 'Metai: 2026' }, { id: 'completion_status:incomplete', label: 'Būsena: Neužpildyta' }] }),
    template: '<div class="max-w-3xl bg-background p-6 text-foreground"><CollectionActiveChips :chips="chips" :total="9" /></div>',
  }),
};

export const MeetingRows: Story = {
  render: () => ({
    components: { MeetingCollectionRow },
    setup: () => ({ meetings }),
    template: `
      <ul class="max-w-3xl divide-y divide-border border-y border-border bg-background text-foreground">
        <li v-for="(meeting, index) in meetings" :key="meeting.id">
          <MeetingCollectionRow :meeting="meeting" :pinned="index === 0" />
        </li>
      </ul>`,
  }),
};

export const Loading: Story = {
  render: () => ({
    components: { CollectionSkeleton },
    template: '<div class="max-w-3xl bg-background p-6 text-foreground"><CollectionSkeleton :rows="4" /></div>',
  }),
};

export const EmptyAndNoResults: Story = {
  render: () => ({
    components: { EmptyState },
    template: `
      <div class="grid max-w-4xl gap-8 bg-background p-6 text-foreground md:grid-cols-2">
        <EmptyState mode="empty" title="Posėdžių dar nėra" description="Čia atsiras institucijų posėdžiai." action-label="Fiksuoti posėdį" />
        <EmptyState mode="no-results" />
      </div>`,
  }),
};

export const MeetingRowsDark: Story = {
  ...MeetingRows,
  globals: { surface: 'admin', theme: 'dark' },
};

export const FilterBarOpenDark: Story = {
  ...FilterBarOpen,
  globals: { surface: 'admin', theme: 'dark' },
};
