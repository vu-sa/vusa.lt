<template>
  <IndexPageLayout title="Pareigybės ėjimo laikotarpiai" model-name="dutiables" :can-use-routes="canUseRoutes"
    :columns="columns" :paginated-models="dutiables" :icon="Icons.DUTIABLE" />
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import {
  type DataTableColumns,
  type DataTableSortState,
} from "naive-ui";
import { computed, provide, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import { tenantColumn } from "@/Composables/dataTableColumns";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  dutiables: PaginatedModels<App.Entities.Dutiable[]>;
}>();

const canUseRoutes = {
  create: false,
  show: false,
  edit: true,
  destroy: true,
};

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  name: false,
  start_date: false,
  end_date: false,
});

provide("sorters", sorters);

const filters = ref<Record<string, any>>({
  //"tenant.id": [],
  "types.id": [],
});

provide("filters", filters);

// ! Don't forget that columns must be computed for the filters to update
const columns = computed<DataTableColumns<App.Entities.Dutiable>>(() => {
  return [
    {
      title() {
        return $t("forms.fields.title");
      },
      key: "name",
      sorter: true,
      sortOrder: sorters.value.name,
      maxWidth: 300,
      ellipsis: {
        tooltip: true,
      },
      render(row) {
        return `${row.duty.name} (${row.dutiable.name})`;
      },
    },
    //{
    //  ...tenantColumn(filters, usePage().props.tenants),
    //  render(row) {
    //    return $t(row.tenant?.shortname ?? "");
    //  },
    //},
    {
      title: "Start Date",
      key: "start_date",
      sorter: true,
      sortOrder: sorters.value.start_date,
      render(row) {
        return row.start_date;
      },
    },
    {
      title: "End Date",
      key: "end_date",
      sorter: true,
      sortOrder: sorters.value.end_date,
      render(row) {
        return row.end_date;
      },
    },
  ];
});
</script>
