<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required>
        <Input id="name" v-model="form.name" type="text" :placeholder="$t('forms.placeholders.short_relationship_name')" />
      </FormFieldWrapper>
      <FormFieldWrapper id="slug" :label="$t('forms.fields.technical_slug')">
        <Input id="slug" v-model="form.slug" type="text" :placeholder="$t('forms.placeholders.slug_example')" />
      </FormFieldWrapper>
      <FormFieldWrapper id="description" :label="$t('forms.fields.description')">
        <Textarea id="description" v-model="form.description" :placeholder="$t('forms.placeholders.short_description')" />
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

const { relationship, rememberKey } = defineProps<{
  relationship: App.Entities.Relationship;
  rememberKey?: 'CreateRelationship';
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = rememberKey ? useForm(rememberKey, relationship) : useForm(relationship);
</script>
