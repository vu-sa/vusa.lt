<template>
  <PageContent title="Nauja registracijos forma" :back-url="route('forms.index')">
    <UpsertModelLayout>
      <FormForm :form
        @submit:form="(form) => form.post(route('forms.store', form.id), { preserveScroll: true })" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { formTemplate as form } from "../../../Types/formTemplates";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import FormForm from "@/Components/AdminForms/FormForm.vue";
</script>
