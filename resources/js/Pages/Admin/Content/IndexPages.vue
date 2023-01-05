<template>
  <IndexPageLayout
    title="Puslapiai"
    model-name="pages"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="pages"
  >
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { DataTableColumns } from "naive-ui";
import { h, ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import AsideHeader from "@/Components/AsideHeaders/AsideHeaderContent.vue";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  pages: PaginatedModels<App.Models.Page[]>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const padaliniaiFilterOptions = ref(
  usePage().props.value.padaliniai.map((padalinys) => {
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

const columns: DataTableColumns<App.Models.News> = [
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
      return h(PreviewModelButton, {
        mainRoute: "main.page",
        padalinysRoute: "padalinys.page",
        mainProps: {
          lang: row.lang,
          permalink: row.permalink,
        },
        padalinysProps: {
          lang: row.lang,
          permalink: row.permalink,
          padalinys: row.padalinys?.alias,
        },
        padalinysShortname: row.padalinys?.shortname,
      });
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
