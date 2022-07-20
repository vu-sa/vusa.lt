<template>
  <AdminLayout title="Puslapiai" :create-u-r-l="route('pages.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput
        payload-name="title"
        @reset-paginate="pagination.page = 1"
      />
      <NDataTable
        remote
        size="small"
        :data="props.pages.data"
        :columns="columns"
        :pagination="pagination"
        @update:page="handlePageChange"
        @update:filters="handleFiltersChange"
      >
      </NDataTable>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { DataTableColumns, NButton, NDataTable } from "naive-ui";
import { Inertia } from "@inertiajs/inertia";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { h, reactive, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import route from "ziggy-js";

interface PaginatedPages extends PaginatedObject {
  data: Array<Page>;
}

const props = defineProps<{
  pages: PaginatedPages;
}>();

const pagination = reactive({
  itemCount: props.pages.total,
  page: props.pages.current_page,
  pageCount: props.pages.last_page,
  pageSize: 20,
  showQuickJumper: true,
});

// console.log(usePage().props.value.padaliniai);

const createColumns = (): DataTableColumns<Page> => {
  return [
    {
      title: "Pavadinimas",
      key: "title",
      ellipsis: true,
      width: 300,
      render(row) {
        return h(
          Link,
          {
            href: route("pages.edit", { id: row.id }),
          },
          { default: () => row.title }
        );
      },
    },
    {
      title: "Padalinys",
      key: "padalinys.id",
      filterMultiple: true,
      filterOptionValues: padaliniaiFilterOptionValues,
      filterOptions: padaliniaiFilterOptions,
      filter: true,
      render(row) {
        return row.padalinys.shortname;
      },
    },
    {
      title: "Sukurta",
      key: "created_at",
      sorter: "default",
      defaultSortOrder: "descend",
    },
    {
      title: "Nuoroda",
      key: "permalink",
      // ellipsis: true,
      // width: 400,
      render(row) {
        return h(
          NButton,
          {
            size: "small",
            onClick: () => {
              if (row.padalinys.shortname == "VU SA") {
                window.open(
                  route("main.page", {
                    lang: row.lang,
                    permalink: row.permalink,
                  }),
                  "_blank"
                );
              } else {
                window.open(
                  route("padalinys.page", {
                    lang: row.lang,
                    permalink: row.permalink,
                    padalinys: row.padalinys.alias,
                  }),
                  "_blank"
                );
              }
            },
          },
          { default: () => "Pasižiūrėti" }
        );
      },
    },
  ];
};

const padaliniaiFilterOptions = ref(
  usePage().props.value.padaliniai.map((padalinys) => {
    return {
      label: padalinys.shortname,
      value: padalinys.id,
    };
  })
);

const padaliniaiFilterOptionValues = ref([]);

padaliniaiFilterOptions.value.unshift({
  label: "VU SA",
  value: 16,
});

const columns = createColumns();
const loading = ref(false);

const handlePageChange = (page) => {
  loading.value = true;
  pagination.page = page;
  Inertia.get(
    route("pages.index"),
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

const handleFiltersChange = (filters) => {
  console.log(filters);
  loading.value = true;
  Inertia.get(
    route("pages.index"),
    {
      page: pagination.page,
      padaliniai: filters["padalinys.id"],
    },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        padaliniaiFilterOptionValues.value = filters["padalinys.id"];
        loading.value = false;
      },
    }
  );
};
</script>
