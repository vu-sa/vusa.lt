import type { Meta, StoryObj } from '@storybook/vue3-vite';

import SeasonCountdown from './SeasonCountdown.vue';

/** Stands in for the Pradžia hero's right side, where the countdown sits. */
const heroBand = '<div class="relative h-40 max-w-3xl bg-background text-foreground"><div class="absolute inset-y-0 right-7 pt-3"><story /></div></div>';

/** A start this far from now, so the stories always show a live countdown. */
const startsIn = (hours: number, minutes = 0) => new Date(Date.now() + (hours * 60 + minutes) * 60_000).toISOString();

const meta: Meta<typeof SeasonCountdown> = {
  title: 'Seasons/Countdown',
  component: SeasonCountdown,
  globals: { surface: 'admin' },
  args: { startsAt: startsIn(4 * 24 + 13, 20), variant: 'hero' },
};

export default meta;
type Story = StoryObj<typeof meta>;

/** Pradžia's hero from md up: a fir and the days and hours left. */
export const Hero: Story = { decorators: [() => ({ template: heroBand })] };
export const HeroDark: Story = { decorators: [() => ({ template: heroBand })], globals: { surface: 'admin', theme: 'dark' } };
/** The last day counts hours, the last hour minutes. */
export const LastDay: Story = { args: { startsAt: startsIn(5, 30) }, decorators: [() => ({ template: heroBand })] };
export const LastHour: Story = { args: { startsAt: startsIn(0, 42) }, decorators: [() => ({ template: heroBand })] };

/** Phones: a quiet row under the greeting. */
export const Phone: Story = {
  args: { variant: 'row' },
  decorators: [() => ({ template: '<div class="max-w-sm bg-background p-4 text-foreground"><story /></div>' })],
  globals: { surface: 'admin', viewport: { value: 'mobile1', isRotated: false } },
};
