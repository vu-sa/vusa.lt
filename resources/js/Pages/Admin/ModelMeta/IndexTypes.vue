<template>
  <AdminIndexPage
    model-name="types"
    entity-name="type"
    title="Turinio tipai"
    :paginated-models="types"
    :column-builder="buildColumns"
    :icon="Icons.TYPE"
    :can-use-routes="{
      create: true,
      show: false,
      edit: true,
      destroy: true
    }"
  />
</template>

<script setup lang="tsx">
import { type DataTableColumns } from "naive-ui";
import { type Ref } from "vue";

import { TableFilters } from "@/Composables/useTableState";
import AdminIndexPage from "@/Components/Layouts/IndexModel/AdminIndexPage.vue";
import Icons from "@/Types/Icons/regular";

defineProps<{
  types: PaginatedModels<App.Entities.Type>;
}>();

// Column builder function
const buildColumns = (sorters: Ref<Record<string, any>>, filters: Ref<TableFilters>): DataTableColumns<App.Entities.Type> => {
  return [
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
};
</script>
