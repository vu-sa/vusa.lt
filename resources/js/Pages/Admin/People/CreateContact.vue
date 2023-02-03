<template>
  <PageContent title="Naujas kontaktas">
    <UpsertModelLayout :errors="$page.props.errors" :model="contactTemplate">
      <ContactForm :contact="contactTemplate" model-route="contacts.store" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import { contactTemplate } from "@/Types/formTemplates";
import ContactForm from "@/Components/AdminForms/ContactForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  // duties: App.Entities.Duty[];
}>();
</script>
