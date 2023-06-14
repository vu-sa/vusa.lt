<template>
  <IndexPageLayout
    title="Ištekliai"
    model-name="resources"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="resources"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import type { DataTableColumns } from "naive-ui";

import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  resources: PaginatedModels<App.Entities.Resource>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: false,
  destroy: false,
};

// add columns
const columns: DataTableColumns<App.Entities.Resource> = [
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
