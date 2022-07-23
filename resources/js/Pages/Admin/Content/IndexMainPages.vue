<template>
  <AdminLayout title="Pradinis puslapis" :create-url="route('mainPage.create')">
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
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from "@/components/Admin/Layouts/AdminLayout.vue";
import route from "ziggy-js";

import AsideHeader from "../../../components/Admin/Headers/AsideHeaderContent.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";

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
    title: "Nuoroda",
    key: "link",
  },

  {
    title: "Kalba",
    key: "lang",
  },
];
</script>
