import type { Meta, StoryObj } from '@storybook/vue3-vite';

import AttentionQueue from './AttentionQueue.vue';
import CoordinatorCard from './CoordinatorCard.vue';
import HomeSection from './HomeSection.vue';
import RecentlyEditedList from './RecentlyEditedList.vue';

const meta: Meta = {
  title: 'Home/Pradžia',
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' }, layout: 'fullscreen' },
};

export default meta;
type Story = StoryObj;

const day = 24 * 60 * 60 * 1000;
const iso = (offset: number) => new Date(Date.now() + offset * day).toISOString();

const tasks = [
  { id: '1', name: 'Užpildyti darbotvarkę', due_date: iso(-3), is_overdue: true, taskable_type: 'meeting', taskable_id: 'm1', taskable: { id: 'm1', name: 'Senato posėdis' } },
  { id: '2', name: 'Patvirtinti rezervaciją', due_date: iso(2), is_overdue: false, taskable_type: 'reservation', taskable_id: 'r1', taskable: { id: 'r1', name: 'Projektorius' } },
  { id: '3', name: 'Fiksuoti posėdį', due_date: iso(20), is_overdue: false, taskable_type: 'institution', taskable_id: 'i1', taskable: { id: 'i1', name: 'VU MIF studentų atstovybė' } },
];

export const AttentionQueueWithTasks: Story = {
  render: () => ({
    components: { AttentionQueue },
    setup: () => ({ tasks, stats: { total: 3, overdue: 1, dueSoon: 1 } }),
    template: '<div class="max-w-3xl bg-background p-6 text-foreground"><AttentionQueue :tasks="tasks" :stats="stats" more-href="/mano/tasks" /></div>',
  }),
};

export const AttentionQueueEmpty: Story = {
  render: () => ({
    components: { AttentionQueue },
    setup: () => ({ stats: { total: 0, overdue: 0, dueSoon: 0 } }),
    template: '<div class="max-w-3xl bg-background p-6 text-foreground"><AttentionQueue :tasks="[]" :stats="stats" more-href="/mano/tasks" /></div>',
  }),
};

export const CoordinatorAndRecent: Story = {
  render: () => ({
    components: { CoordinatorCard, RecentlyEditedList, HomeSection },
    setup: () => ({
      coordinator: { name: 'Jonas Jonaitis', email: 'jonas@vusa.lt', profile_photo_path: null, duty: 'Padalinio koordinatorius' },
      records: [
        { type: 'meeting', id: 'm1', title: 'Senato posėdis', href: '/mano/meetings/m1', changed_at: iso(-0.1) },
        { type: 'institution', id: 'i1', title: 'VU MIF studentų atstovybė', href: '/mano/institutions/i1', changed_at: iso(-2) },
      ],
    }),
    template: `
      <div class="grid max-w-4xl gap-8 bg-background p-6 text-foreground lg:grid-cols-2">
        <CoordinatorCard :coordinator="coordinator" />
        <RecentlyEditedList :records="records" />
        <HomeSection title="Artimiausi posėdžiai" empty empty-text="artimiausiu metu nieko nesuplanuota" />
      </div>`,
  }),
};

/** The ink band inverts in dark mode; axe checks the contrast of that inversion here. */
export const AttentionQueueDark: Story = {
  ...AttentionQueueWithTasks,
  globals: { surface: 'admin', theme: 'dark' },
};
