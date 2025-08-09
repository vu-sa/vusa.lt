<template>
  <IndexPageLayout
    title="Rolės"
    model-name="roles"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="roles"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import type { DataTableColumns } from "naive-ui";

import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  roles: PaginatedModels<App.Entities.Role>;
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: true,
  destroy: true,
};

// add columns
const columns: DataTableColumns<App.Entities.Role> = [
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
