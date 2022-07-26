<template>
  <AdminLayout title="Nauja pareiga">
    <UpsertModelLayout :errors="$attrs.errors" :model="duty">
      <DutyForm
        :duty="duty"
        model-route="duties.store"
        :duty-types="dutyTypes"
      />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import DutyForm from "@/Components/Admin/Forms/DutyForm.vue";
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
};
</script>
