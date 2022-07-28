<template>
  <AdminLayout :title="calendar.title">
    <UpsertModelLayout :errors="$attrs.errors" :model="calendar">
      <CalendarForm
        model-route="calendar.update"
        delete-model-route="calendar.destroy"
        :calendar="calendar"
        :categories="categories"
        :images="images"
      />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import CalendarForm from "@/Components/Admin/Forms/CalendarForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  calendar: App.Models.Calendar;
  images: any;
  categories: App.Models.Category[];
}>();
</script>
