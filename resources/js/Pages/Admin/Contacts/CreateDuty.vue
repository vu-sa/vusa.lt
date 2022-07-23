<template>
  <AdminLayout title="Nauja pareiga">
    <UpsertModelLayout :errors="$attrs.errors" :model="duty">
      <DutyForm :duty="duty" model-route="duties.store" />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from "@/components/Admin/Layouts/AdminLayout.vue";
import DutyForm from "@/components/Admin/Forms/DutyForm.vue";
import UpsertModelLayout from "@/components/Admin/Layouts/UpsertModelLayout.vue";

const duty = {
  name: "",
  description: "",
  email: null,
  institution: {},
};
</script>
