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
import type { DataTableColumns } from "naive-ui";

import { formatStaticTime } from "@/Utils/IntlTime";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  meetings: PaginatedModels<App.Entities.Meeting[]>;
}>();

const canUseRoutes = {
  create: false,
  show: true,
  edit: false,
  destroy: true,
};

const columns: DataTableColumns<App.Entities.Meeting> = [
  {
    title: "Pradžios laikas",
    key: "start_time",
    minWidth: 200,
    render(row) {
      return formatStaticTime(new Date(row.start_time), {
        year: "numeric",
        month: "long",
        day: "2-digit",
      });
    },
  },
  {
    title: "Institucija",
    key: "institutions",
    minWidth: 200,
    render(row) {
      return row.institutions.length === 0
        ? "Neturi institucijos"
        : row.institutions?.map((institution) => institution.name).join(", ");
    },
  },
  {
    title: "Susitikimo darbotvarkė",
    key: "agendaItems",
    render(row) {
      return row.agenda_items.length === 0
        ? ""
        : row.agenda_items?.map((agendaItem) => agendaItem.title).join(", ");
    },
  },
];
</script>
