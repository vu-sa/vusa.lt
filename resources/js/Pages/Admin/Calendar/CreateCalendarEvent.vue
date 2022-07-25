<template>
  <AdminLayout title="Naujas įvykis">
    <UpsertModelLayout :errors="$attrs.errors" :model="calendar">
      <CalendarForm
        model-route="calendar.store"
        :calendar="calendar"
        :categories="categories"
      />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import CalendarForm from "@/Components/Admin/Forms/CalendarForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  categories: App.Models.Category[];
}>();

const calendar = {
  title: "",
  date: null,
  description: "",
  location: "",
  category: null,
  url: "",
  attributes: {},
};
</script>
