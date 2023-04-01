<template>
  <IndexPageLayout
    title="Iniciatyvos"
    model-name="initiatives"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="initiatives"
    :icon="Icons.INITIATIVE"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

import Icons from "@/Types/Icons/regular";
import type { DataTableColumns } from "naive-ui";

defineProps<{
  initiatives: PaginatedModels<App.Entities.Initiative>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const columns: DataTableColumns = [
  {
    title: "Pavadinimas",
    key: "title",
  },
];
</script>
