<template>
  <PageContent title="Nauja registracijos forma" :back-url="route('forms.index')">
    <UpsertModelLayout>
      <FormForm :form :assignable-tenants
        @submit:form="handleFormSubmitted" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import { formTemplate as form } from '../../../Types/formTemplates';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import FormForm from '@/Components/AdminForms/FormForm.vue';

defineProps<{
  assignableTenants: App.Entities.Tenant[];
}>();

// Since the form has nested properties, we don't use useForm() helper and use router instead
function handleFormSubmitted(form: any) {
  router.post(route('forms.store'), form);
}
</script>
