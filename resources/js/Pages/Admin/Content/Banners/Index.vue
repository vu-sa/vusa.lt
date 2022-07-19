<template>
  <AdminLayout title="Baneriai">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <NDataTable
      class="main-card"
      :data="props.banners"
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
import { h, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";

const props = defineProps({
  banners: Object,
});

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "title",
      render(row) {
        return h(
          "span",
          {
            class: row.is_active ? "text-green-700 font-bold" : "text-red-700",
          },
          row.title
        );
      },
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
      Inertia.visit(route("banners.edit", { id: row.id }));
    },
  };
};
</script>
