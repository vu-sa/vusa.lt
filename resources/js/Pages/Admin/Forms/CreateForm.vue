<template>
  <PageContent :title="$t('Nauja registracijos forma')" :back-url="route('forms.index')">
    <UpsertModelLayout>
      <FormForm :form :assignable-tenants :field-model-fields :field-model-options
        @submit:form="handleFormSubmitted" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { formTemplate } from '../../../Types/formTemplates';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import FormForm from '@/Components/AdminForms/FormForm.vue';

defineProps<{
  assignableTenants: App.Entities.Tenant[];
  fieldModelOptions: Record<string, any>[];
  fieldModelFields: Record<string, any>[];
}>();

// Clone so the shared template object isn't mutated by the editor.
const form = structuredClone(formTemplate);

function handleFormSubmitted(form: any) {
  form.post(route('forms.store'));
}
</script>
