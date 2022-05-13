<template>
  <AdminLayout title="Rolės">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <NDataTable
      class="main-card"
      :data="props.roles"
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
  roles: Object,
});

const a = "a";

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "name",
    },
    {
      title: "Alias",
      key: "alias",
    },
  ];
};

const columns = ref(createColumns());

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("roles.edit", { id: row.id }));
    },
  };
};
</script>
