<template>
  <IndexPageLayout
    title="Turinio tipai"
    model-name="relationships"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="types"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { DataTableColumns } from "naive-ui";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  types: PaginatedModels<App.Models.Type>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

// add columns
const columns: DataTableColumns<App.Models.Type> = [
  {
    title: "ID",
    key: "id",
  },
  {
    title: "Pavadinimas",
    key: "title",
  },
  {
    title: "Slug",
    key: "slug",
  },
  {
    title: "Modelis",
    key: "model_type",
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
