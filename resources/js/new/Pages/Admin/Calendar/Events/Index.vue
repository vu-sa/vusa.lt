<template>
  <AdminLayout title="Renginiai">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <NDataTable
      :data="props.calendar"
      :columns="columns"
      :row-props="rowProps"
    >
    </NDataTable>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";
import { NDataTable } from "naive-ui";
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";

const props = defineProps({
  calendar: Object,
});

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "title",
    },
    {
      title: "Data",
      key: "date",
    },
  ];
};

const columns = ref(createColumns());

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("calendar.edit", { id: row.permalink }));
    },
  };
};
</script>