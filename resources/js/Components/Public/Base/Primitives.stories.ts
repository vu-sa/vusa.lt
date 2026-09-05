import type { Meta, StoryObj } from '@storybook/vue3-vite';

import AccessibilityMenu from './AccessibilityMenu.vue';
import CheckControl from './CheckControl.vue';
import DatePlate from './DatePlate.vue';
import HairlineList from './HairlineList.vue';
import HairlineRow from './HairlineRow.vue';
import HeaderWordmark from './HeaderWordmark.vue';
import MediaFrame from './MediaFrame.vue';
import ReadingSizeControl from './ReadingSizeControl.vue';
import SectionBand from './SectionBand.vue';
import ShareButton from './ShareButton.vue';
import StatCell from './StatCell.vue';
import TagChip from './TagChip.vue';

import { Button } from '@/Components/ui/button';

/**
 * The public primitives, side by side. This page is the catalogue: check here before building a
 * new component, and switch the toolbar's Theme to confirm anything you add survives both.
 */
const meta: Meta = {
  title: 'Public/Base/Primitives',
  tags: ['autodocs'],
  parameters: { a11y: { test: 'error' } },
};

export default meta;
type Story = StoryObj;

/** A 1×1 transparent GIF: stories must not depend on the network to render. */
const PIXEL = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

export const Tags: Story = {
  render: () => ({
    components: { TagChip, SectionBand },
    template: `
      <SectionBand spacing="tight">
        <div class="flex flex-wrap items-center gap-3">
          <TagChip label="Atstovavimas" />
          <TagChip label="Renginiai" variant="outline" />
          <TagChip label="Gidai" variant="muted" />
          <TagChip label="Su nuoroda" href="#" variant="muted" />
        </div>
      </SectionBand>
    `,
  }),
};

export const Buttons: Story = {
  render: () => ({
    components: { Button, SectionBand },
    template: `
      <SectionBand spacing="tight">
        <div class="flex flex-wrap items-center gap-3">
          <Button variant="brand">SUŽINOK DAUGIAU</Button>
          <Button variant="outline">Rodyti daugiau</Button>
          <Button variant="ghost">Atšaukti</Button>
        </div>
      </SectionBand>
    `,
  }),
};

export const DatePlates: Story = {
  render: () => ({
    components: { DatePlate, MediaFrame, SectionBand },
    setup: () => ({ PIXEL }),
    template: `
      <SectionBand spacing="tight">
        <div class="flex items-start gap-8">
          <DatePlate :date="new Date(2026, 8, 3)" />
          <MediaFrame :src="PIXEL" alt="" class="max-w-sm">
            <div class="p-3"><DatePlate :date="new Date(2026, 8, 12)" /></div>
          </MediaFrame>
        </div>
      </SectionBand>
    `,
  }),
};

/** The row idiom that replaces cards for lists. */
export const Hairlines: Story = {
  render: () => ({
    components: { HairlineList, HairlineRow, DatePlate, SectionBand, TagChip },
    template: `
      <SectionBand spacing="tight">
        <HairlineList>
          <HairlineRow
            eyebrow="Renginiai"
            title="„AD ASTRA“ kviečia atrasti daugiau nei studijas"
            meta="Penktadienį, 4 rugsėjo · 12:00 · VU Didysis Kiemas"
            href="#"
          >
            <template #leading><DatePlate :date="new Date(2026, 8, 4)" /></template>
            <template #trailing><TagChip label="Festivalis" variant="muted" /></template>
          </HairlineRow>
          <HairlineRow
            eyebrow="Gidai"
            title="Gyvenimas VU bendrabutyje: ką verta žinoti prieš įsikuriant"
            meta="2026 m. rugpjūčio 12 d."
            href="#"
          >
            <template #leading><DatePlate :date="new Date(2026, 7, 12)" /></template>
          </HairlineRow>
        </HairlineList>
      </SectionBand>
    `,
  }),
};

/** Hairline-divided stats strip — no boxes; the numbers carry it. */
export const Stats: Story = {
  render: () => ({
    components: { StatCell, SectionBand },
    template: `
      <SectionBand spacing="tight">
        <div class="grid divide-y divide-border border-y border-border sm:grid-cols-3 sm:divide-x sm:divide-y-0">
          <StatCell value="30+" label="Metų" description="Atstovaujame VU studentų interesams." />
          <StatCell value="16" label="Padalinių" />
          <StatCell value="1000+" label="Savanorių" />
        </div>
      </SectionBand>
    `,
  }),
};

/**
 * Landscape by default and grayscale by house rule. The square frame is included only to show
 * what news images must *not* be cropped to.
 */
export const Media: Story = {
  render: () => ({
    components: { MediaFrame, SectionBand },
    setup: () => ({ PIXEL }),
    template: `
      <SectionBand spacing="tight">
        <div class="grid gap-6 sm:grid-cols-3">
          <MediaFrame :src="PIXEL" alt="Studentai paskaitoje" ratio="16/10" />
          <MediaFrame :src="PIXEL" alt="Studentai paskaitoje" ratio="16/9" :grayscale="false" />
          <MediaFrame :src="PIXEL" alt="Studentai paskaitoje" ratio="4/3" scrim />
        </div>
      </SectionBand>
    `,
  }),
};

/** Both marks. `official` ships today; `wordmark` is the redesign's, pending brand sign-off. */
export const Wordmarks: Story = {
  render: () => ({
    components: { HeaderWordmark, SectionBand },
    template: `
      <SectionBand spacing="tight">
        <div class="flex flex-wrap items-center gap-12">
          <HeaderWordmark variant="official" />
          <HeaderWordmark variant="wordmark" />
        </div>
      </SectionBand>
    `,
  }),
};

/**
 * The public on/off control. Used instead of `ui/switch`, which is a `rounded-full` pill and so
 * survives the surface's zeroed radius scale.
 */
export const Checks: Story = {
  render: () => ({
    components: { CheckControl, SectionBand },
    data: () => ({ contrast: true, underline: false }),
    template: `
      <SectionBand spacing="tight">
        <div class="max-w-sm border-y border-border">
          <CheckControl v-model="contrast" label="Didelis kontrastas" class="border-b border-border" />
          <CheckControl v-model="underline" label="Pabraukti nuorodas" />
        </div>
      </SectionBand>
    `,
  }),
};

/** Open it and change a setting — the whole page rescales. */
export const Accessibility: Story = {
  render: () => ({
    components: { AccessibilityMenu, SectionBand },
    template: `
      <SectionBand spacing="tight">
        <div class="flex items-center gap-4">
          <AccessibilityMenu />
          <p class="text-sm text-muted-foreground">Skaitytojo nustatymai išsaugomi naršyklėje.</p>
        </div>
      </SectionBand>
    `,
  }),
};

/**
 * The article's own reading-size stepper, distinct from the site-wide text size in the
 * accessibility menu: this scales only the body it wraps, and is reached where the reading
 * happens. Step it up and watch the paragraph below grow while the heading does not — the CSS
 * (`.reading-scale` in app.css) claims `p` and `li` only, so the hierarchy cannot invert.
 */
export const ReadingSize: Story = {
  render: () => ({
    components: { ReadingSizeControl, SectionBand },
    template: `
      <SectionBand spacing="tight">
        <div class="max-w-2xl">
          <ReadingSizeControl>
            <h3 class="mb-3 text-xl font-bold text-foreground">Antraštė nesikeičia</h3>
            <p class="leading-relaxed text-muted-foreground">
              Vilniaus universiteto Studentų atstovybė vienija studentus (-es) visuose fakultetuose
              ir atstovauja jų interesams universiteto sprendimų priėmimo organuose.
            </p>
          </ReadingSizeControl>
        </div>
      </SectionBand>
    `,
  }),
};

/**
 * Share this page. In a browser with a native share sheet it opens that; everywhere else it
 * copies the link and says so with a toast. A dismissed share sheet does nothing at all — see
 * `useShareLink`.
 */
export const Share: Story = {
  render: () => ({
    components: { SectionBand, ShareButton },
    template: `
      <SectionBand spacing="tight">
        <div class="flex flex-wrap items-center gap-4">
          <ShareButton title="Pradedama kandidatų registracija" />
          <ShareButton title="Pradedama kandidatų registracija" variant="brand" />
        </div>
      </SectionBand>
    `,
  }),
};
