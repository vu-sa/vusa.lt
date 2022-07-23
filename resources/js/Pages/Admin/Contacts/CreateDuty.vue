<template>
  <AdminLayout title="Nauja pareiga">
    <UpsertModelLayout :errors="$attrs.errors" :model="duty">
      <DutyForm :duty="duty" model-route="duties.store" />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import DutyForm from "@/Components/Admin/Forms/DutyForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const duty = {
  name: "",
  description: "",
  email: null,
  institution: {},
};
</script>
