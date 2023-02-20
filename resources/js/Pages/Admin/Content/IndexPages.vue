<template>
  <IndexPageLayout
    title="Puslapiai"
    model-name="pages"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="pages"
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
  pages: PaginatedModels<App.Entities.Page[]>;
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
    width: 50,
  },
  {
    title: "Pavadinimas",
    key: "title",
    minWidth: 200,
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
      return row.padalinys.shortname;
    },
  },
  {
    title: "Sukurta",
    key: "created_at",
    minWidth: 100,
    ellipsis: {
      tooltip: true,
    },
  },
];
</script>
