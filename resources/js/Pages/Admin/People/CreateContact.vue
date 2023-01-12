<template>
  <PageContent title="Naujas kontaktas">
    <UpsertModelLayout :errors="$page.props.errors" :model="contact">
      <ContactForm :contact="contact" model-route="contacts.store" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">

import ContactForm from "@/Components/AdminForms/ContactForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";



defineProps<{
  // duties: App.Entities.Duty[];
}>();

const contact = {
  name: "",
  email: "",
  phone: null,
  // duties: [],
  profile_photo_path: null,
};
</script>
