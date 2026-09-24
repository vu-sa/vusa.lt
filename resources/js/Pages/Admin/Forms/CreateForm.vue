<template>
  <FormForm
    :form
    :assignable-tenants
    :field-model-fields
    :field-model-options
    @submit:form="handleFormSubmitted"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';

import FormForm from '@/Components/AdminForms/FormForm.vue';
import { formTemplate } from '@/Types/formTemplates';

defineProps<{
  assignableTenants: App.Entities.Tenant[];
  fieldModelOptions: Record<string, unknown>[];
  fieldModelFields: Record<string, unknown>[];
}>();

const form = structuredClone(formTemplate);

function handleFormSubmitted(form: unknown) {
  (form as InertiaForm<Record<string, unknown>>).post(route('forms.store'));
}
</script>
