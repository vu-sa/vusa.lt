<template>
  <AdminLayout title="Naujas įvykis">
    <UpsertModelLayout :errors="$attrs.errors" :model="calendar">
      <CalendarForm model-route="calendar.store" :calendar="calendar" />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import CalendarForm from "@/components/Admin/Forms/CalendarForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const calendar = {
  title: "",
  date: null,
  description: "",
  category: null,
  url: "",
};
</script>
