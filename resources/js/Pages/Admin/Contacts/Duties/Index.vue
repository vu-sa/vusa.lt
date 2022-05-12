<template>
  <AdminLayout title="Pareigos" :createURL="create_url">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <NDataTable
      class="main-card"
      remote
      :data="props.duties.data"
      :columns="columns"
      :row-props="rowProps"
      :pagination="pagination"
      @update:page="handlePageChange"
    >
    </NDataTable>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { NDataTable } from "naive-ui";
import { ref, reactive } from "vue";
import { Inertia } from "@inertiajs/inertia";
import AsideHeader from "../AsideHeader.vue";

const props = defineProps({
  duties: Object,
  create_url: String,
});

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "name",
    },
    {
      title: "El. paštas",
      key: "email",
    },
  ];
};

const columns = ref(createColumns());
const loading = ref(false);

const pagination = reactive({
  itemCount: props.duties.total,
  page: props.duties.current_page,
  pageCount: props.duties.last_page,
  pageSize: 20,
  showQuickJumper: true,
});

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("duties.edit", { id: row.id }));
    },
  };
};

const handlePageChange = (page) => {
  loading.value = true;
  pagination.page = page;
  Inertia.get(
    route("duties.index"),
    { page: page },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        loading.value = false;
      },
    }
  );
};
</script>
