<template>
  <IndexPageLayout
    title="Renginiai"
    model-name="calendar"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="calendar"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  calendar: PaginatedModels<App.Entities.Calendar>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const columns = [
  {
    title: "Pavadinimas",
    key: "title",
  },
  {
    title: "Data",
    key: "date",
  },
  {
    title: "Padalinys",
    key: "padalinys.shortname",
  },
  {
    title: "Kategorija",
    key: "category.name",
  },
];
</script>
