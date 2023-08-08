<template>
  <IndexPageLayout
    title="Pradinis puslapis"
    model-name="mainPage"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="mainPage"
    :icon="Icons.MAIN_PAGE"
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
  mainPage: PaginatedModels<App.Entities.MainPage>;
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

const columns = computed<DataTableColumns<App.Entities.MainPage>>(() => [
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
  {
    key: "lang",
    title: "Kalba",
    width: 100,
    filter: true,
    filterOptionValues: filters.value["lang"],
    filterOptions: [
      {
        label: "Lietuvių",
        value: "lt",
      },
      {
        label: "Anglų",
        value: "en",
      },
    ],
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
