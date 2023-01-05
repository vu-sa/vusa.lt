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
import { DataTableColumns } from "naive-ui";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineProps<{
  relationships: PaginatedModels<App.Models.Relationship>;
}>();

defineOptions({
  layout: AdminLayout,
});

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
