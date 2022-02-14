<template>
  <AdminLayout title="Puslapiai">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <NDataTable remote
      class="main-card"
      :data="props.pages.data"
      :columns="columns"
      :row-props="rowProps"
      :pagination="pagination"
    >
    </NDataTable>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";
import { NDataTable } from "naive-ui";
import { ref, reactive } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";

const props = defineProps({
  pages: Object,
});

const pagination = reactive({
  itemCount: props.pages.total,
  page: props.pages.current_page,
  pageCount: props.pages.last_page,
  pageSize: 20,
  showQuickJumper: true,
});

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "title",
    },
    {
      title: "Nuoroda",
      key: "permalink",
    },
    {
      title: "Atnaujinta",
      key: "updated_at",
    }
  ];
};

const columns = ref(createColumns());

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("pages.edit", { id: row.id }));
    },
  };
};
</script>