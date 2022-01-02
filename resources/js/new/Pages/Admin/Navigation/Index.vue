<template>
  <AdminLayout title="Navigacija">
    <NDataTable
      
      :data="props.navigation"
      :columns="columns"
      :row-props="rowProps"
    >
    </NDataTable>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { NDataTable } from "naive-ui";
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";

const props = defineProps({
  navigation: Object,
});

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "text",
    },
    {
      title: "Nuoroda",
      key: "url",
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
      Inertia.visit(route("navigation.edit", { id: row.permalink }));
    },
  };
};
</script>