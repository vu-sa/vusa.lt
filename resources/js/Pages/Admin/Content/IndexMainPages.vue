<template>
  <PageContent title="Pradinis puslapis" :create-url="route('mainPage.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="text" />
      <IndexDataTable
        :model="mainPages"
        :columns="columns"
        edit-route="mainPage.edit"
        destroy-route="mainPage.destroy"
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
import route from "ziggy-js";

import AsideHeader from "@/Components/Admin/Headers/AsideHeaderContent.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";

defineProps<{
  mainPages: PaginatedModels<App.Models.MainPage[]>;
}>();

const columns = [
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
