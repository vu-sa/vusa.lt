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
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  matters: PaginatedModels<App.Models.Matter[]>;
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
    minWidth: 200,
  },
  {
    title: "Institucija",
    key: "institution_id",
  },
];
</script>
