<template>
  <AdminLayout title="Kontaktai">
    <NDataTable
      class="main-card"
      remote
      :data="props.users.data"
      :loading="loading"
      :columns="columns"
      :pagination="pagination"
      :row-props="rowProps"
      @update:page="handlePageChange"
    ></NDataTable>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { NDataTable } from "naive-ui";
import { ref, reactive } from "vue";
import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
  users: Object,
});

// Parse paginated user data into columns

const createColumns = () => {
  return [
    {
      title: "Vardas",
      key: "name",
    },
    {
      title: "El. paštas",
      key: "email",
    },
    {
      title: "Telefonas",
      key: "phone",
    }
  ];
};

const pagination = reactive({
  itemCount: props.users.total,
  page: props.users.current_page,
  pageCount: props.users.last_page,
  pageSize: 20,
  showQuickJumper: true,
});

const columns = ref(createColumns());
const loading = ref(false);

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("users.edit", { id: row.permalink }));
    },
  };
};

const handlePageChange = (page) => {
  loading.value = true;
  pagination.page = page;
  Inertia.get(route('users.index'), { page: page }, {
    preserveState: true,
    preserveScroll: true, 
    onSuccess: () => {
      loading.value = false;
    }
  });
};
</script>