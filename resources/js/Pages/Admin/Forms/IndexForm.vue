<template>
  <IndexPageLayout title="Formos" model-name="forms" :can-use-routes="canUseRoutes" :columns
    :paginated-models="forms" />
</template>

<script setup lang="tsx">
import type { DataTableColumns } from "naive-ui";

import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  forms: PaginatedModels<App.Entities.Form>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

// add columns
const columns: DataTableColumns<App.Entities.Form> = [
  {
    title: "ID",
    key: "id",
    width: 60,
  },
  {
    title: "Pavadinimas",
    key: "name",
  },
  {
    title: "Nuoroda",
    key: "path",
  },
];
</script>
