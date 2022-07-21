<template>
  <AdminLayout title="Kontaktai" :create-url="route('users.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput
        payload-name="name"
        @reset-paginate="pagination.page = 1"
      />
      <NDataTable
        remote
        :data="props.users.data"
        :loading="loading"
        :columns="columns"
        :pagination="pagination"
        :row-props="rowProps"
        @update:page="handlePageChange"
      ></NDataTable>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { NDataTable, NInput } from "naive-ui";
import { debounce } from "lodash";
import { reactive, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../../../../components/Admin/Headers/AsideHeaderContacts.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";

interface PaginatedUsers extends PaginatedModels {
  data: Array<User>;
}

const props = defineProps<{
  users: PaginatedUsers;
}>();

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
    },
  ];
};

const pagination = ref({
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
      Inertia.visit(route("users.edit", { id: row.id }));
    },
  };
};

const handlePageChange = (page) => {
  loading.value = true;
  pagination.value.page = page;
  Inertia.get(
    route("users.index"),
    { page: page },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        loading.value = false;

        pagination.value = {
          itemCount: props.users.total,
          page: props.users.current_page,
          pageCount: props.users.last_page,
          pageSize: 20,
          showQuickJumper: true,
        };
      },
    }
  );
};
</script>
