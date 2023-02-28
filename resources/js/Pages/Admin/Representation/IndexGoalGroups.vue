<template>
  <IndexPageLayout
    title="Tikslų grupės"
    model-name="goalGroups"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="goalGroups"
    :icon="Icons.GOAL_GROUP"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

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
