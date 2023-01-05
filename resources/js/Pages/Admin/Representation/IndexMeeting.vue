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
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  meetings: PaginatedModels<App.Models.InstitutionMeeting[]>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const columns: DataTableColumns<App.Models.InstitutionMeeting> = [
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
