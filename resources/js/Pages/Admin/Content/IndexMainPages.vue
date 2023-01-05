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
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import AsideHeader from "@/Components/AsideHeaders/AsideHeaderContent.vue";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineOptions({ layout: AdminLayout });

defineProps<{
  mainPages: PaginatedModels<App.Models.MainPage>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const columns: DataTableColumns<App.Models.MainPage> = [
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
