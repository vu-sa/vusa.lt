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
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  calendar: PaginatedModels<App.Models.Calendar>;
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
