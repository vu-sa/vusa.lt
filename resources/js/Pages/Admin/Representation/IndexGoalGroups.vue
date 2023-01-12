<template>
  <IndexPageLayout
    title="Tikslų grupės"
    model-name="goalGroups"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="goalGroups"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineProps<{
  goalGroups: PaginatedModels<App.Entities.GoalGroup>;
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
];
</script>
