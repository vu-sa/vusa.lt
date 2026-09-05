import type { Meta, StoryObj } from '@storybook/vue3-vite';

import DisplayHeading from './DisplayHeading.vue';
import SectionBand from './SectionBand.vue';

/**
 * The signature headline block. Switch the toolbar's Theme to see the brand rule move between
 * VU SA red (light) and amber (dark), and Surface to see it fall back to the admin palette.
 */
const meta: Meta<typeof DisplayHeading> = {
  title: 'Public/Base/DisplayHeading',
  component: DisplayHeading,
  tags: ['autodocs'],
  // New code with no legacy debt, so an axe violation here is a defect, not a backlog item.
  parameters: { a11y: { test: 'error' } },
  argTypes: {
    size: { control: 'select', options: ['sm', 'md', 'lg', 'xl'] },
    as: { control: 'select', options: ['h1', 'h2', 'h3'] },
    rule: { control: 'boolean' },
  },
  args: {
    eyebrow: 'Naujienos',
    title: 'Kas naujo bendruomenėje',
    size: 'md',
    as: 'h2',
    rule: true,
  },
  render: args => ({
    components: { DisplayHeading, SectionBand },
    setup: () => ({ args }),
    template: '<SectionBand><DisplayHeading v-bind="args" /></SectionBand>',
  }),
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const WithLead: Story = {
  args: {
    eyebrow: 'Renginių kalendorius',
    title: 'Renginiai',
    lead: 'Sek visus VU studentų renginius bei įvykius – nuo koncertų iki atstovavimo iniciatyvų.',
    size: 'lg',
    as: 'h1',
  },
};

/** Page-title scale, as used on a listing page's title band. */
export const PageTitle: Story = {
  args: { title: 'Naujienų archyvas', eyebrow: 'Naujienos', size: 'xl', as: 'h1' },
};

/** No rule — for a heading already sitting inside a ruled block. */
export const WithoutRule: Story = {
  args: { rule: false, eyebrow: undefined },
};

/** Long Lithuanian headlines are the real stress case: they must balance, not orphan a word. */
export const LongTitle: Story = {
  args: {
    eyebrow: 'Atstovavimas',
    title: 'Pradedama kandidatų (-čių) į VU Senato studentų atstovus (-es) registracija',
    size: 'lg',
    as: 'h1',
  },
};

/**
 * Every size at once — the check that the scale actually steps, rather than four values that
 * look the same at desktop width.
 */
export const Sizes: Story = {
  render: () => ({
    components: { DisplayHeading, SectionBand },
    template: `
      <SectionBand>
        <div class="flex flex-col gap-12">
          <DisplayHeading v-for="s in ['sm', 'md', 'lg', 'xl']" :key="s"
            :size="s" :eyebrow="s" :title="'Bendruomenės jausmas'" />
        </div>
      </SectionBand>
    `,
  }),
};
