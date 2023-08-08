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
import type { DataTableColumns, DataTableSortState } from "naive-ui";

import { usePage } from "@inertiajs/vue3";
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

const columns = computed<DataTableColumns<App.Entities.Banner>>(() => {
  return [
    {
      title: "Pavadinimas",
      key: "title",
      sorter: true,
      sortOrder: sorters.value.name,
      render(row) {
        return (
          <span
            class={row.is_active ? "font-bold text-green-700" : "text-red-700"}
            href={route("banners.edit", { id: row.id })}
          >
            {row.title}
          </span>
        );
      },
    },
    {
      title: "Padalinys",
      key: "padalinys.id",
      filter: true,
      filterOptionValues: filters.value["padalinys.id"],
      filterOptions: usePage().props.padaliniai.map((padalinys) => {
        return {
          label: padalinys.shortname,
          value: padalinys.id,
        };
      }),
      render(row) {
        return row.padalinys?.shortname;
      },
    },
  ];
});
</script>
