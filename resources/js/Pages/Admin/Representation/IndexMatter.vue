<template>
  <IndexPageLayout
    title="Svarstomi klausimai"
    model-name="matters"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="matters"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  matters: PaginatedModels<App.Entities.Matter[]>;
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: true,
  destroy: true,
};

const columns = [
  {
    title: "Pavadinimas",
    key: "title",
    minWidth: 200,
  },
  {
    title: "Institucija",
    key: "institution_id",
  },
];
</script>
