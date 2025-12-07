<template>
  <IndexPageLayout title="Naujienos" model-name="news" :can-use-routes="canUseRoutes" :columns="columns"
    :paginated-models="news" :icon="Icons.NEWS" />
</template>

<script setup lang="tsx">
import { computed, provide, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import type { DataTableColumns, DataTableSortState } from "naive-ui";

import { formatStaticTime } from "@/Utils/IntlTime";
import { langColumn, tenantColumn } from "@/Composables/dataTableColumns";
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
  duplicate: true,
  destroy: true,
};

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  publish_time: "descend",
  title: false,
});

provide("sorters", sorters);

const filters = ref<Record<string, any>>({
  lang: [],
  "tenant.id": [],
});

provide("filters", filters);

const columns = computed<DataTableColumns<App.Entities.News>>(() => [
  {
    title: "ID",
    key: "id",
    width: 70,
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
            subdomain: row.tenant?.alias ?? "www",
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
      ) : null;
    },
  },
  {
    ...tenantColumn(filters, usePage().props.tenants),
    render(row) {
      return row.tenant?.shortname;
    },
  },
  {
    title: "Paskelbimo data",
    key: "publish_time",
    width: 150,
    sorter: true,
    sortOrder: sorters.value.publish_time,
    ellipsis: {
      tooltip: true,
    },
    render(row) {
      return formatStaticTime(
        new Date(row.publish_time),
        { year: "numeric", month: "numeric", day: "numeric" },
        usePage().props.app.locale
      );
    },
  },
]);
</script>
