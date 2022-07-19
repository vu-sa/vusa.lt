<template>
  <AdminLayout title="Renginiai" :create-u-r-l="route('calendar.create')">
    <NDataTable
      class="main-card"
      remote
      :data="props.calendar.data"
      :columns="columns"
      :row-props="rowProps"
      :pagination="pagination"
      @update:page="handlePageChange"
    >
    </NDataTable>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { NDataTable } from "naive-ui";
import { reactive, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
  calendar: Object,
  create_url: String,
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
const loading = ref(false);

const pagination = reactive({
  itemCount: props.calendar.total,
  page: props.calendar.current_page,
  pageCount: props.calendar.last_page,
  pageSize: 20,
  showQuickJumper: true,
});

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("calendar.edit", { id: row.id }));
    },
  };
};

const handlePageChange = (page) => {
  loading.value = true;
  pagination.page = page;
  Inertia.get(
    route("calendar.index"),
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
