<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <FormFieldWrapper id="name" :label="$t('forms.fields.title')">
        <Input id="name" v-model="form.name" type="text" :placeholder="$t('forms.placeholders.enter_title')" />
      </FormFieldWrapper>
      <FormFieldWrapper id="alias" :label="$t('forms.fields.slug')">
        <Input id="alias" v-model="form.alias" type="text" placeholder="" :disabled="!!form.id" />
      </FormFieldWrapper>
      <FormFieldWrapper id="description" :label="$t('forms.fields.description')">
        <Textarea id="description" v-model="form.description" placeholder="..." />
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import { Textarea } from '@/Components/ui/textarea';
import { Input } from '@/Components/ui/input';

const { category, rememberKey } = defineProps<{
  category: App.Entities.Category;
  rememberKey?: string;
}>();

const form = rememberKey ? useForm(rememberKey, category) : useForm(category);

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();
</script>
