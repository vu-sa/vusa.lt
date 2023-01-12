<template>
  <IndexPageLayout
    title="Leidimai"
    model-name="permissions"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="permissions"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import type { DataTableColumns } from "naive-ui";


import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";



defineProps<{
  permissions: PaginatedModels<any>;
}>();

const canUseRoutes = {
  create: false,
  show: false,
  edit: false,
  destroy: false,
};

// add columns
const columns: DataTableColumns<App.Entities.Permission> = [
  {
    title: "Pavadinimas",
    key: "name",
  },
  {
    title: "Sukurtas",
    key: "created_at",
    render(row) {
      return new Date(row.created_at).toLocaleString("lt-LT");
    },
  },
  {
    title: "Atnaujintas",
    key: "updated_at",
    render(row) {
      return new Date(row.updated_at).toLocaleString("lt-LT");
    },
  },
];
</script>
