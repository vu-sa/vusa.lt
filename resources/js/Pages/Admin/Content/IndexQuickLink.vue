<template>
  <IndexPageLayout title="Greitosios nuorodos" model-name="quickLinks" :can-use-routes="canUseRoutes" :columns="columns"
    :paginated-models="quickLinks" :icon="Icons.QUICK_LINK" />
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { computed, provide, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import type { DataTableColumns, DataTableSortState } from "naive-ui";

import { langColumn, tenantColumn } from "@/Composables/dataTableColumns";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  quickLinks: PaginatedModels<App.Entities.QuickLink>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  text: false,
});

provide("sorters", sorters);

const filters = ref<Record<string, any>>({
  lang: [],
  "padalinys.id": [],
});

provide("filters", filters);

const columns = computed<DataTableColumns<App.Entities.QuickLink>>(() => [
  {
    title: "Pavadinimas",
    key: "text",
    sorter: true,
    sortOrder: sorters.value.name,
    ellipsis: {
      tooltip: true,
    },
    maxWidth: 300,
  },
  {
    ...tenantColumn(filters, usePage().props.tenants),
    render(row) {
      return $t(row.tenant?.shortname);
    },
  },
  {
    ...langColumn(filters),
    render(row) {
      return row.lang === "lt" ? "🇱🇹" : "🇬🇧";
    },
  },
  {
    title: "Nuoroda",
    key: "link",
  },
]);
</script>
