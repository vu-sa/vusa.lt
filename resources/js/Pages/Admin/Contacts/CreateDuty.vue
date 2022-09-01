<template>
  <PageContent title="Nauja pareiga">
    <UpsertModelLayout :errors="$attrs.errors" :model="duty">
      <DutyForm
        :duty="duty"
        model-route="duties.store"
        :duty-types="dutyTypes"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import DutyForm from "@/Components/Admin/Forms/DutyForm.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  dutyTypes: App.Models.DutyType[];
}>();

const duty = {
  name: "",
  description: "",
  email: null,
  institution: {},
  type: {},
  attributes: {
    en: {
      name: "",
      description: "",
    },
  },
};
</script>
