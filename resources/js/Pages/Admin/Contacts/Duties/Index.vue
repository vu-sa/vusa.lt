<template>
  <AdminLayout title="Pareigos" :createURL="route('duties.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <NInput
        class="md:col-span-4 mb-2"
        type="text"
        size="medium"
        round
        placeholder="Ieškoti pagal pavadinimą, el. paštą..."
        @input="handleSearchInput"
        :loading="loading"
      ></NInput>
      <NDataTable
        remote
        :data="props.duties.data"
        :columns="columns"
        :row-props="rowProps"
        :pagination="pagination"
        @update:page="handlePageChange"
      >
      </NDataTable>
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
  duties: Object,
  create_url: String,
});

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

const handleSearchInput = _.debounce((input) => {
  const title = input;
  // if (name.length > 2) {
  loading.value = true;
  Inertia.reload({
    data: { title: title },
    onSuccess: () => {
      console.log(props.duties);
      pagination.value = {
        itemCount: props.duties.total,
        page: 1,
        pageCount: props.duties.last_page,
        pageSize: 20,
        showQuickJumper: true,
      };
      loading.value = false;
      // },
    },
  });
}, 500);
</script>
