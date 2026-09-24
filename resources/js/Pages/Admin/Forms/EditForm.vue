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
import { computed } from 'vue';
import { router, type InertiaForm } from '@inertiajs/vue3';
import { transChoice as $tChoice } from 'laravel-vue-i18n';

import FormForm from '@/Components/AdminForms/FormForm.vue';
import { FormIcon } from '@/Components/icons';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

const props = defineProps<{
  form: App.Entities.Form;
  assignableTenants: App.Entities.Tenant[];
  fieldModelOptions: Record<string, unknown>[];
  fieldModelFields: Record<string, unknown>[];
}>();

const formTitle = computed(() => {
  if (typeof props.form.name === 'object' && props.form.name) {
    return (props.form.name as Record<string, string>).lt || (props.form.name as Record<string, string>).en || '';
  }
  return String(props.form.name ?? '');
});

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminForm(
    $tChoice('entities.form.model', 2),
    'forms.index',
    formTitle.value,
    FormIcon,
  ),
);

function handleFormSubmitted(form: unknown) {
  (form as InertiaForm<Record<string, unknown>>).patch(route('forms.update', props.form.id));
}
</script>
