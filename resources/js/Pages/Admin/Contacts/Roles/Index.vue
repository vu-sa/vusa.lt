<template>
  <AdminLayout title="Rolės">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <NDataTable
      class="main-card"
      :data="roles"
      :columns="columns"
      :row-props="rowProps"
    >
    </NDataTable>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { NDataTable } from "naive-ui";
import { ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../../../../components/Admin/Headers/AsideHeaderContacts.vue";
import route from "ziggy-js";

defineProps<{
  roles: App.Models.Role[];
}>();

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

const rowProps = (row: App.Models.Role) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("roles.edit", { id: row.id }));
    },
  };
};
</script>
