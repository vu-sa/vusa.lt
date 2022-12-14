<template>
  <PageContent title="Nauja pareiga">
    <UpsertModelLayout :errors="$attrs.errors" :model="duty">
      <DutyForm
        :duty="duty"
        model-route="duties.store"
        :duty-types="dutyTypes"
        :duty-institutions="dutyInstitutions"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import DutyForm from "@/Components/AdminForms/DutyForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  dutyTypes: App.Models.DutyType[];
  dutyInstitutions: App.Models.DutyInstitution[];
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
