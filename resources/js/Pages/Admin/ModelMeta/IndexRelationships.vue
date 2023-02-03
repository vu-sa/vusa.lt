<template>
  <IndexPageLayout
    title="Ryšiai"
    model-name="relationships"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="relationships"
  >
  </IndexPageLayout>
</template>

<script setup lang="ts">
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import type { DataTableColumns } from "naive-ui";

defineProps<{
  relationships: PaginatedModels<App.Entities.Relationship>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

// add columns
const columns: DataTableColumns = [
  {
    title: "ID",
    key: "id",
  },
  {
    title: "Pavadinimas",
    key: "name",
  },
  {
    title: "Techninė žymė",
    key: "slug",
  },
  {
    title: "Aprašymas",
    key: "description",
  },
];
</script>
