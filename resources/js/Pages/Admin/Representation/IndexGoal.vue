<template>
  <IndexPageLayout
    title="Tikslai"
    model-name="goals"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="goals"
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
  goals: PaginatedModels<Record<string, any>>;
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
  {
    title: "Tikslo grupė",
    key: "goal_id",
  },
];
</script>
