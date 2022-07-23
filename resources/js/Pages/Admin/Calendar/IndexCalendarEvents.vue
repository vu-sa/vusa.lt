<template>
  <AdminLayout title="Renginiai" :create-url="route('calendar.create')">
    <div class="main-card">
      <IndexSearchInput payload-name="title" />
      <IndexDataTable
        edit-route="calendar.edit"
        :model="calendar"
        :columns="columns"
      />
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref } from "vue";
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import route from "ziggy-js";

defineProps<{
  calendar: PaginatedModels<App.Models.Calendar[]>;
}>();

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "title",
    },
    {
      title: "Data",
      key: "date",
    },
  ];
};

const columns = ref(createColumns());
</script>
