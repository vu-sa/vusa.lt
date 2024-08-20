<template>
  <IndexPageLayout title="Turinio tipai" model-name="types" :can-use-routes="canUseRoutes" :columns="columns"
    :paginated-models="types" :icon="Icons.TYPE" />
</template>

<script setup lang="tsx">
import type { DataTableColumns } from "naive-ui";

import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  types: PaginatedModels<App.Entities.Type>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

// add columns
const columns: DataTableColumns<App.Entities.Type> = [
  {
    title: "ID",
    key: "id",
    width: 60,
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
