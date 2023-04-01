<template>
  <IndexPageLayout
    title="Organizacijos"
    model-name="organizations"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="organizations"
    :icon="Icons.ORGANIZATION"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

import Icons from "@/Types/Icons/regular";
import type { DataTableColumns } from "naive-ui";

defineProps<{
  organizations: PaginatedModels<App.Entities.Organization>;
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
