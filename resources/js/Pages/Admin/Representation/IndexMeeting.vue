<template>
  <IndexPageLayout
    title="Institucijų posėdžiai"
    model-name="meetings"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="meetings"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { DataTableColumns } from "naive-ui";

import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineProps<{
  meetings: PaginatedModels<App.Entities.Meeting[]>;
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: true,
  destroy: true,
};

const columns: DataTableColumns<App.Entities.Meeting> = [
  {
    title: "Institucijos",
    key: "institutions",
    minWidth: 200,
    render(row) {
      return row.institutions
        ?.map((institution) => institution.name)
        .join(", ");
    },
  },
];
</script>
