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
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import DutyForm from "@/Components/AdminForms/DutyForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  dutyTypes: App.Models.Type[];
  institutions: App.Models.Institution[];
}>();

const duty = {
  name: "",
  description: "",
  email: null,
  institution: {},
  type: null,
  attributes: {
    en: {
      name: "",
      description: "",
    },
  },
};
</script>
