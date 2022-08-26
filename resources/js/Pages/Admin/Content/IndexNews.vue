<template>
  <PageContent title="Naujienos" :create-url="route('news.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="title" />
      <IndexDataTable
        destroy-route="news.destroy"
        edit-route="news.edit"
        :model="news"
        :columns="columns"
        @update-filters-value="padaliniaiFilterOptionValues = $event"
      />
    </div>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { DataTableColumns } from "naive-ui";
import { h, ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";

import AsideHeader from "@/Components/Admin/Headers/AsideHeaderContent.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import PreviewModelButton from "@/Components/Admin/Buttons/PreviewModelButton.vue";
import route from "ziggy-js";

defineProps<{
  news: PaginatedModels<App.Models.News[]>;
}>();

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
      return h(PreviewModelButton, {
        mainRoute: "main.news",
        padalinysRoute: "padalinys.news",
        mainProps: {
          newsString: "naujiena",
          lang: row.lang,
          permalink: row.permalink,
        },
        padalinysProps: {
          newsString: "naujiena",
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
      return row.padalinys.shortname;
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
