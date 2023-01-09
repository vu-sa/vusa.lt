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

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  roles: PaginatedModels<App.Entities.Role>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: false,
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
