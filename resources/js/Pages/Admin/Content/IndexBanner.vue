<template>
  <IndexPageLayout
    title="Baneriai"
    model-name="banners"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="banners"
    :icon="Icons.BANNER"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { computed, provide, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import type { DataTableColumns, DataTableSortState } from "naive-ui";

import { trans as $t } from "laravel-vue-i18n";
import { tenantColumn } from "@/Composables/dataTableColumns";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  banners: PaginatedModels<App.Entities.Banner[]>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  title: false,
});

provide("sorters", sorters);

const filters = ref<Record<string, any>>({
  "padalinys.id": [],
});

provide("filters", filters);

const columns = computed<DataTableColumns<App.Entities.Banner>>(() => [
  {
    title: "Pavadinimas",
    key: "title",
    sorter: true,
    sortOrder: sorters.value.name,
    render(row: App.Entities.Banner) {
      return (
        <a
          class={row.is_active ? "font-bold text-green-700" : "text-red-700"}
          href={route("banners.edit", { id: row.id })}
        >
          {row.title}
        </a>
      );
    },
  },
  {
    ...tenantColumn(filters, usePage().props.tenants),
    render(row) {
      return $t(row.tenant?.shortname);
    },
  },
]);
</script>
