import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { Calendar, FileText, Folder, Megaphone, Newspaper, Tags } from 'lucide-vue-next';

import NavigationTiles from './NavigationTiles.vue';

const meta: Meta<typeof NavigationTiles> = {
  title: 'Patterns/NavigationTiles',
  component: NavigationTiles,
  tags: ['autodocs'],
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' } },
};

export default meta;
type Story = StoryObj<typeof meta>;

const svetaine = [
  { key: 'puslapiai', href: '#', label: 'Puslapiai', description: 'Svetainės tekstai ir jų struktūra', icon: FileText },
  { key: 'naujienos', href: '#', label: 'Naujienos', description: 'Straipsniai ir pranešimai svetainėje', icon: Newspaper },
  { key: 'kalendorius', href: '#', label: 'Kalendorius', description: 'Renginiai ir jų anonsai', icon: Calendar },
  { key: 'baneriai', href: '#', label: 'Baneriai', description: 'Partnerių ir akcijų baneriai', icon: Megaphone },
  { key: 'zymos', href: '#', label: 'Žymos', description: 'Naujienų ir turinio žymos', icon: Tags },
  { key: 'failai', href: '#', label: 'Failai', description: 'Nuotraukos ir svetainės failai', icon: Folder },
];

export const FullRows: Story = {
  args: { items: svetaine.slice(0, 4) },
};

/** A partial last row: the rules stop at the last tile instead of running on to the grid's edge. */
export const PartialLastRow: Story = {
  args: { items: svetaine },
};

export const TwoTiles: Story = {
  args: { items: svetaine.slice(0, 2) },
};

export const ThreeColumns: Story = {
  args: { items: svetaine.slice(0, 5), columns: 3 },
};

export const LabelOnly: Story = {
  args: { items: svetaine.slice(0, 3).map(({ description: _description, ...item }) => item) },
};
