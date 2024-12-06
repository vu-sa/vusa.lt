<template>
  <PageContent :title="form.name.lt" :back-url="route('forms.index')">
    <UpsertModelLayout>
      <FormForm :form :assignable-tenants :field-model-fields :field-model-options enable-delete
        @submit:form="(form) => form.patch(route('forms.update', form.id), { preserveScroll: true })"
        @delete="() => router.delete(route('forms.destroy', form.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import FormForm from "@/Components/AdminForms/FormForm.vue";

defineProps<{
  form: App.Entities.Form;
  assignableTenants: App.Entities.Tenant[];
  fieldModelOptions: Record<string, any>[];
  fieldModelFields: Record<string, any>[];
}>();
</script>
