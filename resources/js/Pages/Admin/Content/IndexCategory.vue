<template>
  <IndexPageLayout title="Kategorijos" model-name="categories" :can-use-routes="canUseRoutes" :columns="columns"
    :paginated-models="categories" :icon="Icons.CATEGORY" />
</template>

<script setup lang="tsx">
import type { DataTableColumns } from "naive-ui";

import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  categories: PaginatedModels<App.Entities.Category>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: false,
};

// add columns
const columns: DataTableColumns<App.Entities.Category> = [
  {
    title: "ID",
    key: "id",
  },
  {
    title: "Pavadinimas",
    key: "name",
  },
  {
    title: "Slug",
    key: "alias",
  },
];
</script>
