<template>
  <SheetForm
    v-model:open="open"
    required-note
    :created-at="tag?.created_at"
    :updated-at="tag?.updated_at"
    :title="tag ? $t('Redaguoti žymą') : $t('Nauja žyma')"
    :description="$t('Žyma padeda susieti panašų svetainės turinį.')"
    :processing="form.processing"
    :dirty="form.isDirty"
    @cancel="reset"
    @submit="submit"
  >
    <FormFieldWrapper
      id="tag-name"
      :label="$t('Pavadinimas')"
      required
      :error="form.errors.name || form.errors['name.lt']"
    >
      <MultiLocaleInput id="tag-name" v-model:input="form.name" />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="tag-alias"
      :label="$t('Alias')"
      :hint="$t('Trumpa nuorodos dalis naudojama ten, kur reikia pastovaus adreso.')"
      :error="form.errors.alias"
    >
      <Input
        id="tag-alias"
        :model-value="form.alias ?? ''"
        :placeholder="$t('Pvz: stipendijos')"
        @update:model-value="value => form.alias = String(value) || null"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="tag-description"
      :label="$t('Aprašymas')"
      :error="form.errors.description"
    >
      <MultiLocaleTiptapFormItem v-model:input="form.description" />
    </FormFieldWrapper>

    <label for="tag-is-topic" class="flex min-h-11 items-center gap-3 border-y border-border py-3 text-sm">
      <Checkbox id="tag-is-topic" v-model="form.is_topic" />
      <span>
        <span class="block font-medium">{{ $t('Teminė žyma') }}</span>
        <span class="block text-muted-foreground">{{ $t('Rodoma kaip turinio tema svetainėje.') }}</span>
      </span>
    </label>
  </SheetForm>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import MultiLocaleInput from '@/Components/AdminForms/MultiLocaleInput.vue';
import MultiLocaleTiptapFormItem from '@/Components/AdminForms/MultiLocaleTiptapFormItem.vue';
import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { useSheetCrudForm } from '@/Composables/useSheetCrudForm';

interface TagInput {
  created_at?: string | null;
  updated_at?: string | null;
  id?: string | number;
  name: { lt: string; en: string };
  description: { lt: string; en: string } | null;
  alias: string | null;
  is_topic: boolean;
}

interface TagFormData {
  id?: string | number;
  name: { lt: string; en: string };
  description: { lt: string; en: string };
  alias: string | null;
  is_topic: boolean;
}

const props = defineProps<{ open: boolean; tag?: TagInput | null }>();
const emit = defineEmits<{ 'update:open': [value: boolean]; 'saved': [] }>();

const { open, form, reset, submit } = useSheetCrudForm<TagFormData, TagInput>({
  open: () => props.open,
  onUpdateOpen: value => emit('update:open', value),
  onSaved: () => emit('saved'),
  record: () => props.tag,
  blank: () => ({ name: { lt: '', en: '' }, description: { lt: '', en: '' }, alias: null, is_topic: false }),
  fromRecord: raw => ({
    id: raw.id,
    name: { ...raw.name },
    description: { lt: raw.description?.lt ?? '', en: raw.description?.en ?? '' },
    alias: raw.alias,
    is_topic: raw.is_topic,
  }),
  storeUrl: () => route('tags.store'),
  updateUrl: id => route('tags.update', id),
  visitOptions: { headers: { 'X-Tag-Sheet': 'true' } },
});
</script>
