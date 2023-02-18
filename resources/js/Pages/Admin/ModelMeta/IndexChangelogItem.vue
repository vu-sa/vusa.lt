<template>
  <IndexPageLayout
    title="Pasikeitimai"
    model-name="changelogItems"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="changelogItems"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import type { DataTableColumns } from "naive-ui";

import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  changelogItems: PaginatedModels<App.Entities.ChangelogItem>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

// add columns
const columns: DataTableColumns<App.Entities.ChangelogItem> = [
  {
    title: "ID",
    key: "id",
  },
  {
    title: "Pavadinimas",
    key: "title",
  },
  {
    title: "Data",
    key: "date",
  },
];
</script>
