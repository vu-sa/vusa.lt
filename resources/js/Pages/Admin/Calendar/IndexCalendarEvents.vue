<template>
  <IndexPageLayout
    title="Renginiai"
    model-name="calendar"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="calendar"
    :icon="Icons.CALENDAR"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { computed, provide, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import type { DataTableSortState } from "naive-ui";

import { formatStaticTime } from "@/Utils/IntlTime";
import Icons from "@/Types/Icons/regular";

const props = defineProps<{
  calendar: PaginatedModels<App.Entities.Calendar[]>;
  allCategories: App.Entities.Category[];
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
  "category.alias": [],
  "padalinys.id": [],
});

watch(
  filters,
  (newFilters) => {
    console.log(newFilters);
  },
  { deep: true },
);

provide("filters", filters);

const columns = computed(() => {
  return [
    {
      title: "Pavadinimas",
      key: "title",
      sorter: true,
      sortOrder: sorters.value.name,
      maxWidth: 200,
      ellipsis: {
        tooltip: true,
      },
    },
    {
      title: "Data",
      key: "date",
      render(row) {
        return formatStaticTime(row.date, {
          year: "numeric",
          month: "long",
          day: "numeric",
          hour: "numeric",
          minute: "numeric",
        });
      },
    },
    {
      title: "Kategorija",
      key: "category.alias",
      filter: true,
      filterOptionValues: filters.value["category.alias"],
      filterOptions: props.allCategories.map((category) => {
        return {
          label: category.name,
          value: category.alias,
        };
      }),
      render(row: App.Entities.Calendar) {
        return row.category?.name;
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
      render(row: App.Entities.Calendar) {
        return row.padalinys?.shortname;
      },
    },
  ];
});
</script>
