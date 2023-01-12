<template>
  <PageContent title="Nauja pareiga">
    <UpsertModelLayout :errors="$page.props.errors" :model="duty">
      <DutyForm
        :duty="duty"
        model-route="duties.store"
        :duty-types="dutyTypes"
        :institutions="institutions"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import DutyForm from "@/Components/AdminForms/DutyForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  dutyTypes: App.Entities.Type[];
  institutions: App.Entities.Institution[];
}>();

const duty = {
  name: "",
  description: "",
  email: null,
  institution: {},
  type: null,
  extra_attributes: {
    en: {
      name: "",
      description: "",
    },
  },
};
</script>
