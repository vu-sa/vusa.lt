<template>
  <IndexPageLayout
    title="Puslapiai"
    model-name="pages"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="pages"
    :icon="Icons.PAGE"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { h, provide, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import type { DataTableColumns, DataTableSortState } from "naive-ui";

import { langColumn, padalinysColumn } from "@/Composables/dataTableColumns";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";

defineProps<{
  pages: PaginatedModels<App.Entities.Page[]>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  created_at: "descend",
  title: false,
});

provide("sorters", sorters);

const filters = ref<Record<string, any>>({
  lang: [],
  "padalinys.id": [],
});

provide("filters", filters);

const columns: DataTableColumns<App.Entities.News> = [
  {
    title: "ID",
    key: "id",
    width: 50,
  },
  {
    title: "Pavadinimas",
    key: "title",
    className: "text-wrap",
    sorter: true,
    sortOrder: sorters.value.title,
    minWidth: 150,
    width: 200,
    resizable: true,
  },
  {
    // title: "Nuoroda",
    key: "permalink",
    // ellipsis: true,
    width: 55,
    render(row) {
      return row.permalink ? (
        <PreviewModelButton
          publicRoute="page"
          routeProps={{
            lang: row.lang,
            subdomain: row.padalinys?.alias ?? "www",
            permalink: row.permalink,
          }}
        />
      ) : (
        ""
      );
    },
  },
  {
    ...langColumn(filters),
    render(row) {
      return row.lang === "lt" ? "🇱🇹" : "🇬🇧";
    },
  },
  {
    key: "other_lang_id",
    title: "Kitos kalbos puslapis",
    render(row) {
      return row.other_lang_id
        ? h(
            "a",
            {
              href: route("pages.edit", { id: row.other_lang_id }),
              target: "_blank",
            },
            row.other_lang_id,
          )
        : "";
    },
  },
  {
    ...padalinysColumn(filters, usePage().props.padaliniai),
    render(row) {
      return row.padalinys.shortname;
    },
  },
  {
    title: "Sukurta",
    key: "created_at",
    sorter: (a, b) => {
      return (
        new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
      );
    },
    sortOrder: sorters.value.created_at,
    minWidth: 100,
    ellipsis: {
      tooltip: true,
    },
  },
];
</script>
