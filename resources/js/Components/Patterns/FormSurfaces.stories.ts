import type { Meta, StoryObj } from '@storybook/vue3-vite';

import FormPage from '@/Components/Layouts/FormPage.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

const meta: Meta = {
  title: 'Patterns/Form surfaces',
  globals: { surface: 'admin' },
  parameters: { a11y: { test: 'error' }, layout: 'fullscreen' },
};

export default meta;

const fields = `
  <FormSection title="Kas tai?" description="Pagrindinė pareigybės informacija.">
    <div class="space-y-1.5">
      <Label for="story-name">Pavadinimas</Label>
      <Input id="story-name" model-value="Pirmininkas" />
    </div>
  </FormSection>
  <FormSection title="Kur tai rodoma?" badge="Matoma vusa.lt" public-marker>
    <div class="space-y-1.5">
      <Label for="story-email">El. paštas</Label>
      <Input id="story-email" model-value="vusa@vusa.lt" />
    </div>
  </FormSection>
`;

export const EditForm: StoryObj = {
  render: () => ({
    components: { FormPage, FormSection, Input, Label },
    template: `<FormPage title="Pirmininkas" lead="VU SA MIF" back-href="/mano/duties" back-label="Pareigybės">${fields}</FormPage>`,
  }),
};

export const CreateFormWithErrors: StoryObj = {
  render: () => ({
    components: { FormPage, FormSection, Input, Label },
    template: `<FormPage title="Nauja pareigybė" mode="create" dirty :errors="{ name: 'Pavadinimas yra privalomas' }" :field-ids="{ name: 'story-name' }">${fields}</FormPage>`,
  }),
};

export const Sheet: StoryObj = {
  render: () => ({
    components: { SheetForm, FormSection, Input, Label },
    template: `
      <SheetForm :open="true" title="Priskirti narį" description="Pasirink narį ir nurodyk, nuo kada jis eina šias pareigas." save-label="Priskirti">
        <div class="space-y-1.5">
          <Label for="story-member">Narys</Label>
          <Input id="story-member" />
        </div>
        <template #danger-zone>
          <p class="text-sm text-muted-foreground">Pavojingi veiksmai — po laukais, ne apačioje.</p>
        </template>
      </SheetForm>
    `,
  }),
};

export const Confirm: StoryObj = {
  render: () => ({
    components: { ConfirmDialog },
    template: `<ConfirmDialog :open="true" title="Ištrinti priskyrimą?" description="Kadencijos įrašas bus pašalintas." confirm-label="Ištrinti" destructive />`,
  }),
};
