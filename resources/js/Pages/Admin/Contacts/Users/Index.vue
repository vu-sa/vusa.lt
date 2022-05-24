<template>
  <AdminLayout title="Kontaktai">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <NInput
        class="md:col-span-4 mb-2"
        type="text"
        size="medium"
        round
        placeholder="Ieškoti pagal vardą, el. paštą..."
        @input="handleSearchInput"
        :loading="loading"
      ></NInput>
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

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { NDataTable, NInput } from "naive-ui";
import { ref, reactive } from "vue";
import { Inertia } from "@inertiajs/inertia";
import AsideHeader from "../AsideHeader.vue";

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
    },
  ];
};

const pagination = ref({});

pagination.value = {
  itemCount: props.users.total,
  page: props.users.current_page,
  pageCount: props.users.last_page,
  pageSize: 20,
  showQuickJumper: true,
};

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
  pagination.page = page;
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

const handleSearchInput = _.debounce((input) => {
  const name = input;
  // if (name.length > 2) {
  loading.value = true;
  Inertia.reload({
    data: { name: name },
    onSuccess: () => {
      console.log(props.users);
      pagination.value = {
        itemCount: props.users.total,
        page: 1,
        pageCount: props.users.last_page,
        pageSize: 20,
        showQuickJumper: true,
      };
      loading.value = false;
      // },
    },
  });
}, 500);
</script>
