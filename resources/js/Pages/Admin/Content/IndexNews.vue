<template>
  <IndexPageLayout
    title="Naujienos"
    model-name="news"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="news"
    :icon="Icons.NEWS"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { computed, provide, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import type { DataTableColumns, DataTableSortState } from "naive-ui";

import { langColumn, padalinysColumn } from "@/Composables/dataTableColumns";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";

defineProps<{
  news: PaginatedModels<App.Entities.News>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  publish_time: "descend",
  title: false,
});

provide("sorters", sorters);

const filters = ref<Record<string, any>>({
  lang: [],
  "padalinys.id": [],
});

provide("filters", filters);

const columns = computed<DataTableColumns<App.Entities.News>>(() => [
  {
    title: "ID",
    key: "id",
    width: 40,
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
          publicRoute="news"
          routeProps={{
            lang: row.lang,
            news: row.permalink,
            newsString: "naujiena",
            subdomain: row.padalinys?.alias ?? "www",
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
    title: "Kitos kalbos naujiena",
    maxWidth: 110,
    ellipsis: {
      tooltip: true,
    },
    render(row) {
      return row.other_language_news ? (
        <a
          href={route("news.edit", { id: row.other_language_news.id })}
          target="_blank"
        >
          {row.other_language_news?.title}
        </a>
      ) : (
        ""
      );
    },
  },
  {
    ...padalinysColumn(filters, usePage().props.padaliniai),
    render(row) {
      return row.padalinys?.shortname;
    },
  },
  {
    title: "Paskelbimo data",
    key: "publish_time",
    width: 150,
    sorter: (a, b) => {
      return (
        new Date(a.publish_time).getTime() - new Date(b.publish_time).getTime()
      );
    },
    sortOrder: sorters.value.publish_time,
    ellipsis: {
      tooltip: true,
    },
  },
]);
</script>
