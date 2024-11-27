<template>
  <PageContent :title="form.name.lt" :back-url="route('forms.index')">
    <UpsertModelLayout>
      <FormForm :form
        enable-delete
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
}>();
</script>
