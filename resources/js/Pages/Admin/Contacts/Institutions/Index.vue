<template>
  <AdminLayout title="Institucijos">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="search" />
      <NDataTable
        :data="props.dutyInstitutions"
        :columns="columns"
        :row-props="rowProps"
      >
      </NDataTable>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { NDataTable } from "naive-ui";
import { ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";

const props = defineProps<{
  dutyInstitutions: DutyInstitution[];
}>();

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
      key: "padalinys.shortname",
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
