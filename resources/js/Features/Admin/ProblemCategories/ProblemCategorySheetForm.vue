<template>
  <SheetForm
    v-model:open="open"
    required-note
    :created-at="category?.created_at"
    :updated-at="category?.updated_at"
    :title="category ? $t('Redaguoti kategoriją') : $t('Nauja kategorija')"
    :description="$t('Kategorijos bendros visiems padaliniams: pagal jas filtruojamos problemos.')"
    :processing="form.processing"
    :dirty="form.isDirty"
    @cancel="reset"
    @submit="submit"
  >
    <FormFieldWrapper
      id="problem-category-name"
      :label="$t('forms.fields.title')"
      required
      :error="form.errors.name || form.errors['name.lt']"
    >
      <MultiLocaleInput id="problem-category-name" v-model:input="form.name" :placeholder="PROBLEM_CATEGORY_PLACEHOLDERS.name" />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="problem-category-description"
      :label="$t('forms.fields.description')"
      :hint="$t('Aprašymas rodomas problemos formoje, kai renkamasi kategorija.')"
      :error="form.errors.description"
    >
      <MultiLocaleInput
        id="problem-category-description"
        v-model:input="form.description"
        input-type="textarea"
        :placeholder="PROBLEM_CATEGORY_PLACEHOLDERS.description"
      />
    </FormFieldWrapper>
  </SheetForm>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import MultiLocaleInput from '@/Components/AdminForms/MultiLocaleInput.vue';
import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { useSheetCrudForm } from '@/Composables/useSheetCrudForm';
import { PROBLEM_CATEGORY_PLACEHOLDERS } from '@/Constants/I18n/Placeholders';

export interface ProblemCategoryInput {
  created_at?: string | null;
  updated_at?: string | null;
  id?: number;
  name: { lt: string; en: string };
  description: { lt: string; en: string } | null;
}

interface ProblemCategoryFormData {
  id?: number;
  name: { lt: string; en: string };
  description: { lt: string; en: string };
}

const props = defineProps<{ open: boolean; category?: ProblemCategoryInput | null }>();
const emit = defineEmits<{ 'update:open': [value: boolean]; 'saved': [] }>();

const { open, form, reset, submit } = useSheetCrudForm<ProblemCategoryFormData, ProblemCategoryInput>({
  open: () => props.open,
  onUpdateOpen: value => emit('update:open', value),
  onSaved: () => emit('saved'),
  record: () => props.category,
  blank: () => ({ name: { lt: '', en: '' }, description: { lt: '', en: '' } }),
  fromRecord: ({ id, name, description }) => ({
    id,
    name,
    description: description ?? { lt: '', en: '' },
  }),
  storeUrl: () => route('problemCategories.store'),
  updateUrl: id => route('problemCategories.update', id),
});
</script>
