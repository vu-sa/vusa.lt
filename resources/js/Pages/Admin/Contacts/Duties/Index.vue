<template>
  <AdminLayout title="Pareigos" :create-u-r-l="route('duties.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="title" />
      <NDataTable
        remote
        :data="duties.data"
        :columns="columns"
        :row-props="rowProps"
        :pagination="pagination"
        @update:page="handlePageChange"
      >
      </NDataTable>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { NDataTable, NInput } from "naive-ui";
import { reactive, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";
import route from "ziggy-js";

interface PaginatedDuties extends PaginatedObject {
  data: Array<Page>;
}

const props = defineProps<{
  duties: PaginatedDuties;
}>();

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "name",
      width: 300,
      ellipsis: {
        tooltip: true,
      },
    },
    {
      title: "Tipas",
      key: "type.name",
    },
    {
      title: "El. paštas",
      key: "email",
    },
    {
      title: "Institucija",
      key: "institution.id",
      render(row) {
        return row.institution.short_name ?? row.institution.name;
      },
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
