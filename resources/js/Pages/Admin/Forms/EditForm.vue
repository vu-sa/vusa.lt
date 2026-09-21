<template>
  <FormForm
    :form
    :assignable-tenants
    :field-model-fields
    :field-model-options
    enable-delete
    @submit:form="handleFormSubmitted"
    @delete="() => router.delete(route('forms.destroy', form.id))"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import FormForm from '@/Components/AdminForms/FormForm.vue';

const props = defineProps<{
  form: App.Entities.Form;
  assignableTenants: App.Entities.Tenant[];
  fieldModelOptions: Record<string, unknown>[];
  fieldModelFields: Record<string, unknown>[];
}>();

function handleFormSubmitted(form: unknown) {
  (form as InertiaForm<Record<string, unknown>>).patch(route('forms.update', props.form.id));
}
</script>
