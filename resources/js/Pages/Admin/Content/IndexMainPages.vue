<template>
  <AdminLayout title="Pradinis puslapis">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="text" />
      <IndexDataTable :model="mainPages" :columns="columns" />
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { h, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import route from "ziggy-js";

import AsideHeader from "../../../components/Admin/Headers/AsideHeaderContent.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";

defineProps<{
  mainPages: PaginatedModels<App.Models.MainPage[]>;
}>();

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "text",
      ellipsis: true,
      width: 300,

      render(row: App.Models.MainPage) {
        return h(
          Link,
          {
            href: route("mainPage.edit", { id: row.id }),
            class: "hover:text-vusa-red transition",
          },
          { default: () => row.text }
        );
      },
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
};

const columns = ref(createColumns());
</script>
