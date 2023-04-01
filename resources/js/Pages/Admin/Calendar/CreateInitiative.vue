<template>
  <PageContent title="Nauja iniciatyva">
    <UpsertModelLayout :errors="$page.props.errors" :model="initiative">
      <InitiativeForm
        model-route="initiatives.store"
        :initiative="initiative"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import InitiativeForm from "@/Components/AdminForms/InitiativeForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const initiative = {
  title: {
    lt: "",
    en: "",
  },
  description: {
    lt: "",
    en: "",
  },
  logo: "",
  cover: "",
  participation_url: "",
  email: "",
  phone: "",
  start_date: null,
  end_date: null,
};
</script>
