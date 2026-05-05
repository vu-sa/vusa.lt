<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        <MdSuspenseWrapper directory="reservations" :locale="$page.props.app.locale" file="main-info" />
      </template>
      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required>
        <MultiLocaleInput v-model:input="form.name" :placeholder="RESOURCE_CATEGORY_PLACEHOLDERS.name" />
      </FormFieldWrapper>
      <FormFieldWrapper id="description" :label="$t('forms.fields.description')">
        <MultiLocaleInput v-model:input="form.description" input-type="textarea"
          :placeholder="RESOURCE_CATEGORY_PLACEHOLDERS.description" />
      </FormFieldWrapper>
      <Suspense>
        <FluentIconSelect :icon="form.icon" @update:icon="(value) => form.icon = value" />
      </Suspense>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import FluentIconSelect from '../FormItems/FluentIconSelect.vue';
import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import MdSuspenseWrapper from '@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue';
import { RESOURCE_CATEGORY_PLACEHOLDERS } from '@/Constants/I18n/Placeholders';

const props = defineProps<{
  resourceCategory: any;
  rememberKey?: 'CreateResourceCategory';
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = props.rememberKey
  ? useForm(props.rememberKey, props.resourceCategory)
  : useForm(props.resourceCategory);
</script>
