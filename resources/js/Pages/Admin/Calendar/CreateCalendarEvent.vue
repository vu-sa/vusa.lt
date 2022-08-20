<template>
  <PageContent title="Naujas įvykis">
    <UpsertModelLayout :errors="$attrs.errors" :model="calendar">
      <CalendarForm
        model-route="calendar.store"
        :calendar="calendar"
        :categories="categories"
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
import CalendarForm from "@/Components/Admin/Forms/CalendarForm.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
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
