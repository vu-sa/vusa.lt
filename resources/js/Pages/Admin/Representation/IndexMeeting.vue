<template>
  <IndexPageLayout :title="capitalize($tChoice('entities.meeting.model', 2))" model-name="meetings"
    :can-use-routes="canUseRoutes" :columns="columns" :paginated-models="meetings" :icon="Icons.MEETING" />
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { computed, provide, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import type { DataTableColumns, DataTableSortState } from "naive-ui";

import { capitalize } from "@/Utils/String";
import { formatStaticTime } from "@/Utils/IntlTime";
import { tenantColumn } from "@/Composables/dataTableColumns";
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
  start_time: "descend",
});

provide("sorters", sorters);

const filters = ref<Record<string, any>>({
  "tenants.id": [],
});

provide("filters", filters);

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
      ...tenantColumn(filters, usePage().props.tenants),
      render(row) {
        return row.tenants?.length === 0
          ? ""
          : $t(row.tenants?.map((tenant) => tenant.shortname).join(", "));
      },
    },
    {
      title: "Institucija",
      key: "institutions",
      minWidth: 200,
      resizable: true,
      render(row) {
        return row.institutions?.length === 0
          ? "Neturi institucijos"
          : row.institutions?.map((institution) => institution.name).join(", ");
      },
    },
    {
      title: "Susitikimo darbotvarkė",
      key: "agendaItems",
      maxWidth: 500,
      resizable: true,
      ellipsis: {
        tooltip: true,
      },
      render(row) {
        return row.agenda_items?.length === 0
          ? ""
          : row.agenda_items?.map((agendaItem) => agendaItem.title).join(", ");
      },
    },
  ];
});
</script>
