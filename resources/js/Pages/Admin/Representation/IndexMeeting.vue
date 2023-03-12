<template>
  <IndexPageLayout
    title="Institucijų posėdžiai"
    model-name="meetings"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="meetings"
    :icon="Icons.MEETING"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { computed, provide, ref } from "vue";
import type { DataTableColumns, DataTableSortState } from "naive-ui";

import { formatStaticTime } from "@/Utils/IntlTime";
import { updateSorters } from "@/Utils/DataTable";
import Icons from "@/Types/Icons/regular";
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

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  start_time: false,
});

provide("sorters", { sorters, updateSorters });

const columns = computed<DataTableColumns<App.Entities.Meeting>>(() => {
  return [
    {
      title: "Pradžios laikas",
      key: "start_time",
      sorter: true,
      sortOrder: sorters.value.start_time,
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
      title: "Padalinys",
      key: "padaliniai",
      minWidth: 150,
      render(row) {
        return row.padaliniai.length === 0
          ? "Neturi padalinio"
          : row.padaliniai?.map((padalinys) => padalinys.shortname).join(", ");
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
});
</script>
