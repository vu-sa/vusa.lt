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
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  goals: PaginatedModels<Record<string, any>>;
}>();

const canUseRoutes = {
  create: false,
  show: true,
  edit: false,
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
