<template>
  <IndexPageLayout title="Formos" model-name="forms" :can-use-routes="canUseRoutes" :columns
    :paginated-models="forms" />
</template>

<script setup lang="tsx">
import type { DataTableColumns } from "naive-ui";
import { usePage } from "@inertiajs/vue3";
import { provide, ref } from "vue";
import { trans as $t } from "laravel-vue-i18n";

import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import { tenantColumn } from "@/Composables/dataTableColumns";

defineProps<{
  forms: PaginatedModels<App.Entities.Form>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const filters = ref<Record<string, any>>({
  "tenant.id": [],
  "types.id": [],
});

provide("filters", filters);

// add columns
const columns: DataTableColumns<App.Entities.Form> = [
  {
    title: "Pavadinimas",
    key: "name",
  },
  {
    title: "Nuoroda",
    key: "path",
  },
  {
    ...tenantColumn(filters, usePage().props.tenants),
    render(row) {
      return $t(row.tenant?.shortname ?? "");
    },
  },
];
</script>
