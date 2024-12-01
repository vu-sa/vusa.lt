<template>
  <IndexPageLayout :title="capitalize($tChoice('entities.membership.model', 2))" model-name="memberships"
    :can-use-routes="canUseRoutes" :columns="columns" :paginated-models="memberships" />
</template>

<script setup lang="ts">
import {
  type DataTableColumns,
} from "naive-ui";
import { computed, provide, ref } from "vue";
import { trans as $t } from "laravel-vue-i18n";

import { capitalize } from "@/Utils/String";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import { tenantColumn } from "@/Composables/dataTableColumns";
import { usePage } from "@inertiajs/vue3";

defineProps<{
  memberships: PaginatedModels<App.Entities.Membership>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: false,
};

const filters = ref<Record<string, any>>({
  "tenant.id": [],
});

provide("filters", filters);

const columns = computed<DataTableColumns<App.Entities.Duty>>(() => [
  {
    title: "Pavadinimas",
    key: "name",
  },
  {
    ...tenantColumn(filters, usePage().props.tenants),
    render(row) {
      return $t(row.tenant?.shortname ?? "");
    },
  },
]);
</script>
