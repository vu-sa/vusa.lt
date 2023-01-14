<template>
  <PageContent :title="contact.name" :back-url="route('contacts.index')">
    <UpsertModelLayout :errors="$page.props.errors" :model="contact">
      <ContactForm
        :contact="contact"
        model-route="contacts.update"
        delete-model-route="contacts.destroy"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import ContactForm from "@/Components/AdminForms/ContactForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  contact: App.Entities.Contact;
}>();
</script>
