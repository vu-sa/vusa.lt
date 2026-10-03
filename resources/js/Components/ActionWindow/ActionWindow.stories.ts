import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { CalendarDays, CalendarSearch, History, Landmark } from 'lucide-vue-next';

import ActionChoiceButton from './ActionChoiceButton.vue';
import ActionChoiceList from './ActionChoiceList.vue';
import ActionWindowPrimaryButton from './ActionWindowPrimaryButton.vue';
import ActionWindowScreen from './ActionWindowScreen.vue';
import ReviewRow from './ReviewRow.vue';

import EntityTypeMark from '@/Components/EntityTypeMark.vue';

const meta: Meta = {
  title: 'ActionWindow/Guided flow',
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' }, layout: 'centered' },
};

export default meta;
type Story = StoryObj;

/** The window's frame at its dialog width; the sheet below `md` is the same body at 92dvh. */
const frame = (body: string) => `
  <div class="flex h-[560px] w-[560px] flex-col border border-border bg-popover text-popover-foreground">
    <div class="flex items-center justify-center px-4 py-2">
      <EntityTypeMark type="meeting" label="Naujas posėdis" class="text-xs font-semibold uppercase tracking-wider" />
      <span class="ml-3 text-xs tabular-nums text-muted-foreground">3 / 5</span>
    </div>
    <div class="flex gap-1 px-4" aria-hidden="true">
      <span class="h-0.5 flex-1 bg-foreground/40" /><span class="h-0.5 flex-1 bg-foreground/40" /><span class="h-0.5 flex-1 bg-brand-fill" /><span class="h-0.5 flex-1 bg-border" /><span class="h-0.5 flex-1 bg-border" />
    </div>
    ${body}
  </div>`;

const components = { ActionChoiceButton, ActionChoiceList, ActionWindowPrimaryButton, ActionWindowScreen, EntityTypeMark, ReviewRow };

/** Today and yesterday lead, then the body's usual slot, then the date field. */
export const WhenPresets: Story = {
  render: () => ({
    components: { ...components },
    setup: () => ({ CalendarDays, CalendarSearch, History }),
    template: frame(`
      <ActionWindowScreen title="Kada vyko ar vyks posėdis?" subtitle="Šiandien, vakar arba pagal tai, kada ši institucija posėdžiaudavo iki šiol.">
        <ActionChoiceList>
          <ActionChoiceButton title="Šiandien" description="šeštadienis, rugsėjo 19" :icon="CalendarDays" :show-chevron="false" />
          <ActionChoiceButton title="Vakar" description="penktadienis, rugsėjo 18" :icon="History" :show-chevron="false" />
          <ActionChoiceButton title="antradienis, rugsėjo 22, 18:30" description="Artimiausias įprastas laikas" :icon="CalendarDays" :show-chevron="false" />
          <ActionChoiceButton title="Pasirinkti kitą datą…" :icon="CalendarSearch" />
        </ActionChoiceList>
      </ActionWindowScreen>`),
  }),
};

/** Status roles colour the icon tile; the ordinary state stays plain. */
export const InstitutionPicker: Story = {
  render: () => ({
    components: { ...components },
    setup: () => ({ Landmark }),
    template: frame(`
      <ActionWindowScreen title="Kuriai institucijai?" subtitle="Rodomos institucijos, kuriose eini pareigas.">
        <ActionChoiceList>
          <ActionChoiceButton title="VU MIF studentų atstovybė" description="Paskutinis posėdis 2026 m. gegužės 4 d." :icon="Landmark" tone="danger" />
          <ActionChoiceButton title="VU TSPMI studentų atstovybė" description="Posėdis rugsėjo 24 d." :icon="Landmark" tone="info" />
          <ActionChoiceButton title="VU FLF studentų atstovybė" description="Paskutinis posėdis 2026 m. rugsėjo 2 d." :icon="Landmark" />
        </ActionChoiceList>
      </ActionWindowScreen>`),
  }),
};

export const Review: Story = {
  render: () => ({
    components: { ...components },
    template: frame(`
      <ActionWindowScreen title="Ar viskas gerai?" subtitle="Paspausk ant eilutės, jei nori ką nors pakeisti.">
        <dl class="divide-y divide-border border-y border-border">
          <ReviewRow label="Institucija" value="VU MIF studentų atstovybė" />
          <ReviewRow label="Tipas" value="Gyvas posėdis" />
          <ReviewRow label="Laikas" value="penktadienis, rugsėjo 18, 18:00" />
          <ReviewRow label="Darbotvarkė" value="3 klausimai" />
        </dl>
        <template #footer>
          <ActionWindowPrimaryButton>Fiksuoti posėdį</ActionWindowPrimaryButton>
        </template>
      </ActionWindowScreen>`),
  }),
};

export const ReviewDark: Story = {
  ...Review,
  globals: { surface: 'admin', theme: 'dark' },
};
