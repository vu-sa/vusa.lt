<template>
  <IndexPageLayout
    title="Veiksmai"
    model-name="doings"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="doings"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { DataTableColumns, NTag } from "naive-ui";

import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  doings: PaginatedModels<App.Entities.Doing[]>;
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: true,
  destroy: true,
};

const columns: DataTableColumns<App.Entities.Doing> = [
  {
    title: "Pavadinimas",
    key: "title",
    minWidth: 200,
  },
  {
    title: "Data",
    key: "date",
  },
  {
    title: "Statusas",
    key: "status",
  },
  {
    title: "Svarstomi klausimai",
    key: "matters",
    render(row) {
      return row.goals?.map((goal) => <NTag key={goal.id}>{goal.id}</NTag>);
    },
  },
  {
    title: "Tipai",
    key: "types",
    render(row) {
      return row.types?.map((type) => <NTag key={type.id}>{type.title}</NTag>);
    },
  },
];
</script>
