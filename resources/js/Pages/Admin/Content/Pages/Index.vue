<template>
  <AdminLayout title="Puslapiai" :createURL="route('pages.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <NInput
        class="md:col-span-4 mb-2"
        type="text"
        size="medium"
        round
        placeholder="Ieškoti pagal pavadinimą..."
        @input="handleSearchInput"
        :loading="loading"
      ></NInput>
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

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";
import { NDataTable, NButton, NInput } from "naive-ui";
import { ref, reactive, h } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { Link, usePage } from "@inertiajs/inertia-vue3";

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

// console.log(usePage().props.value.padaliniai);

const createColumns = () => {
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

const columns = ref(createColumns());
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

const handleSearchInput = _.debounce((input) => {
  const title = input;
  // if (name.length > 2) {
  loading.value = true;
  Inertia.reload({
    data: { title: title },
    onSuccess: () => {
      console.log(props.pages);
      pagination.value = {
        itemCount: props.pages.total,
        page: 1,
        pageCount: props.pages.last_page,
        pageSize: 20,
        showQuickJumper: true,
      };
      loading.value = false;
      // },
    },
  });
}, 500);
</script>
