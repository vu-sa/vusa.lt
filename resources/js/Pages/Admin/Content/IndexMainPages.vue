<template>
  <IndexPageLayout
    title="Pradinis puslapis"
    model-name="mainPage"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="mainPages"
  >
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { DataTableColumns } from "naive-ui";

import AsideHeader from "@/Components/AsideHeaders/AsideHeaderContent.vue";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineProps<{
  mainPages: PaginatedModels<App.Entities.MainPage>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const columns: DataTableColumns<App.Entities.MainPage> = [
  {
    title: "Pavadinimas",
    key: "text",
    ellipsis: {
      tooltip: true,
    },
    width: 300,
  },
  {
    title: "Padalinys",
    key: "padalinys.shortname",
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
    title: "Nuoroda",
    key: "link",
  },
];
</script>
