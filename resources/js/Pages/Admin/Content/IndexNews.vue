<template>
  <IndexPageLayout
    title="Naujienos"
    model-name="news"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="news"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { h, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import type { DataTableColumns } from "naive-ui";

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

const padaliniaiFilterOptions = ref(
  usePage().props.padaliniai.map((padalinys) => {
    return {
      label: padalinys.shortname,
      value: padalinys.id,
    };
  })
);

const padaliniaiFilterOptionValues = ref<number[] | null>([]);

padaliniaiFilterOptions.value.unshift({
  label: "VU SA",
  value: 16,
});

const columns: DataTableColumns<App.Entities.News> = [
  {
    title: "ID",
    key: "id",
    width: 60,
  },
  {
    title: "Pavadinimas",
    key: "title",
    minWidth: 200,
    ellipsis: {
      tooltip: true,
    },
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
            newsString: "naujiena",
            padalinys: row.padalinys?.alias ?? "www",
            permalink: row.permalink,
          }}
        />
      ) : (
        ""
      );
    },
  },
  {
    key: "lang",
    title: "Kalba",
    width: 100,
    render(row) {
      return row.lang === "lt" ? "🇱🇹" : "🇬🇧";
    },
  },
  {
    key: "other_lang_id",
    title: "Kitos kalbos puslapis",
    width: 150,
    render(row) {
      return row.other_lang_id
        ? h(
            "a",
            {
              href: route("news.edit", { id: row.other_lang_id }),
              target: "_blank",
            },
            row.other_lang_id
          )
        : "";
    },
  },
  {
    title: "Padalinys",
    key: "padalinys.id",
    width: 150,
    ellipsis: {
      tooltip: true,
    },
    filter: true,
    filterMultiple: true,
    filterOptionValues: padaliniaiFilterOptionValues,
    filterOptions: padaliniaiFilterOptions,
    render(row) {
      return row.padalinys?.shortname;
    },
  },
  {
    title: "Paskelbimo data",
    key: "publish_time",
    width: 150,
    ellipsis: {
      tooltip: true,
    },
  },
];
</script>
