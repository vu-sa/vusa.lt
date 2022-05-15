<template>
  <AdminLayout title="Institucijos">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <NDataTable
      class="main-card"
      :data="props.dutyInstitutions"
      :columns="columns"
      :row-props="rowProps"
      :scroll-x="1200"
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
  dutyInstitutions: Object,
});

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "name",
      ellipsis: true,
      width: 300,
    },
    {
      title: "Trumpas",
      key: "short_name",
    },
    {
      title: "Alias",
      key: "alias",
    },

    {
      title: "Padalinys",
      key: "padalinys_id",
    },
  ];
};

const columns = ref(createColumns());

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("dutyInstitutions.edit", { id: row.id }));
    },
  };
};
</script>
