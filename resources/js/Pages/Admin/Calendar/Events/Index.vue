<template>
  <AdminLayout title="Renginiai" :create-u-r-l="route('calendar.create')">
    <div class="main-card">
      <IndexSearchInput payload-name="title" />
      <IndexDataTable :model="calendar" :columns="columns" />
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { h, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
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
      render(row: App.Models.Calendar) {
        return h(
          Link,
          {
            href: route("calendar.edit", { id: row.id }),
            class: "hover:text-vusa-red transition",
          },
          { default: () => row.title }
        );
      },
    },
    {
      title: "Data",
      key: "date",
    },
  ];
};

const columns = ref(createColumns());
</script>
