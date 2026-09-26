import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { ListTree, Send } from 'lucide-vue-next';
import { ref } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import FormPanel from '@/Components/Patterns/FormPanel.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import FormToggleRow from '@/Components/Patterns/FormToggleRow.vue';
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

/** The v0 page-form shape: fields on the left, panels of settings in the aside. */
export const FormWithAside: StoryObj = {
  render: () => ({
    components: { FormPage, FormFieldWrapper, FormPanel, FormToggleRow, Input },
    setup: () => ({ Send, ListTree, breadcrumbs: ref(true), toc: ref(false) }),
    template: `
      <FormPage title="Socialinės stipendijos" entity-type="page" :available-locales="[]" back-href="/mano/pages" back-label="Puslapiai"
        lead="Atnaujink turinį, struktūrą ir paskelbimo būseną.">
        <FormFieldWrapper id="story-title" label="Pavadinimas" hint="Rodomas naršyklės skirtuke ir paieškoje." :char-count="22" :max-length="60">
          <Input id="story-title" model-value="Socialinės stipendijos" />
        </FormFieldWrapper>
        <template #aside>
          <FormPanel title="Paskelbimas" :icon="Send">
            <FormFieldWrapper id="story-parent" label="Tėvinis puslapis">
              <Input id="story-parent" model-value="Pagalba studentui" />
            </FormFieldWrapper>
          </FormPanel>
          <FormPanel title="Rodymo nustatymai" :icon="ListTree" flush>
            <FormToggleRow v-model="breadcrumbs" label="Rodyti puslapio kelią" hint="„Pradžia / … / Puslapis“ navigacija viršuje." />
            <FormToggleRow v-model="toc" label="Rodyti turinio lentelę" hint="Automatinis skyrių sąrašas šoninėje juostoje." />
          </FormPanel>
        </template>
      </FormPage>
    `,
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
