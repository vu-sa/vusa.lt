<template>
  <SheetForm
    v-model:open="open"
    required-note
    :created-at="category?.created_at"
    :updated-at="category?.updated_at"
    :title="category ? $t('Redaguoti kategoriją') : $t('Nauja kategorija')"
    :description="$t('Kategorija sugrupuoja panašius išteklius, kad juos būtų lengviau rasti.')"
    :processing="form.processing"
    :dirty="form.isDirty"
    @cancel="reset"
    @submit="submit"
  >
    <FormFieldWrapper
      id="category-name"
      :label="$t('forms.fields.title')"
      required
      :error="form.errors.name || form.errors['name.lt']"
    >
      <MultiLocaleInput id="category-name" v-model:input="form.name" :placeholder="RESOURCE_CATEGORY_PLACEHOLDERS.name" />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="category-description"
      :label="$t('forms.fields.description')"
      :error="form.errors.description"
    >
      <MultiLocaleInput
        id="category-description"
        v-model:input="form.description"
        input-type="textarea"
        :placeholder="RESOURCE_CATEGORY_PLACEHOLDERS.description"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="category-icon"
      :label="$t('Ikona')"
      :error="form.errors.icon"
    >
      <Suspense>
        <FluentIconSelect :icon="form.icon" @update:icon="value => form.icon = value" />
      </Suspense>
    </FormFieldWrapper>
  </SheetForm>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import FluentIconSelect from '@/Components/AdminForms/FluentIconSelect.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import MultiLocaleInput from '@/Components/AdminForms/MultiLocaleInput.vue';
import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { useSheetCrudForm } from '@/Composables/useSheetCrudForm';
import { RESOURCE_CATEGORY_PLACEHOLDERS } from '@/Constants/I18n/Placeholders';

interface CategoryInput {
  created_at?: string | null;
  updated_at?: string | null;
  id?: string | number;
  name: { lt: string; en: string };
  description: { lt: string; en: string } | null;
  icon: string | null;
}

interface CategoryFormData {
  id?: string | number;
  name: { lt: string; en: string };
  description: { lt: string; en: string };
  icon: string | null;
}

const props = defineProps<{ open: boolean; category?: CategoryInput | null }>();
const emit = defineEmits<{ 'update:open': [value: boolean]; 'saved': [] }>();

const { open, form, reset, submit } = useSheetCrudForm<CategoryFormData, CategoryInput>({
  open: () => props.open,
  onUpdateOpen: value => emit('update:open', value),
  onSaved: () => emit('saved'),
  record: () => props.category,
  blank: () => ({ name: { lt: '', en: '' }, description: { lt: '', en: '' }, icon: null }),
  fromRecord: raw => ({
    id: raw.id,
    name: raw.name,
    description: raw.description ?? { lt: '', en: '' },
    icon: raw.icon,
  }),
  storeUrl: () => route('resourceCategories.store'),
  updateUrl: id => route('resourceCategories.update', id),
});
</script>
