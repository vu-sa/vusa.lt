<template>
  <IndexPageLayout :title="capitalize($tChoice('entities.training.model', 2))" model-name="trainings" :can-use-routes
    :columns :paginated-models="trainings" />
</template>

<script setup lang="ts">
import {
  type DataTableColumns,
} from "naive-ui";
import { computed } from "vue";

import { capitalize } from "@/Utils/String";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  trainings: PaginatedModels<App.Entities.Membership>;
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: true,
  destroy: false,
};

const columns = computed<DataTableColumns<App.Entities.Duty>>(() => [
  {
    title: "Pavadinimas",
    key: "name",
  },
]);
</script>
