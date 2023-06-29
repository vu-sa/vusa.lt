<template>
  <IndexPageLayout
    title="Rezervacijos"
    model-name="reservations"
    :icon="Icons.RESERVATION"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="reservations"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import type { DataTableColumns } from "naive-ui";

import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  reservations: PaginatedModels<App.Entities.Reservation>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: false,
};

// add columns
const columns: DataTableColumns<App.Entities.Reservation> = [
  {
    title: "Pavadinimas",
    key: "name",
  },
  {
    title: "Sukurtas",
    key: "created_at",
    render(row) {
      return new Date(row.created_at).toLocaleString("lt-LT");
    },
  },
  {
    title: "Atnaujintas",
    key: "updated_at",
    render(row) {
      return new Date(row.updated_at).toLocaleString("lt-LT");
    },
  },
];
</script>
