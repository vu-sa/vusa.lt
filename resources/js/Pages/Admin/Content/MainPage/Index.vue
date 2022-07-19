<template>
  <AdminLayout title="Pradinis puslapis">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <NDataTable
      class="main-card"
      :data="props.mainPage"
      :columns="columns"
      :row-props="rowProps"
    >
    </NDataTable>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import { NDataTable } from "naive-ui";
import { ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";

const props = defineProps({
  mainPage: Object,
});

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "text",
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

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("mainPage.edit", { id: row.id }));
    },
  };
};
</script>
