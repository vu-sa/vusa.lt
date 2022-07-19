<template>
  <AdminLayout title="Naujienos" :create-u-r-l="route('news.create')">
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
        :loading="loading"
        @input="handleSearchInput"
      ></NInput>
      <NDataTable
        remote
        size="small"
        :data="props.news.data"
        :columns="columns"
        :row-props="rowProps"
        :pagination="pagination"
        @update:page="handlePageChange"
        @update:filters="handleFiltersChange"
      >
      </NDataTable>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { NButton, NDataTable, NInput } from "naive-ui";
import { h, reactive, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";

const props = defineProps({
  news: Object,
});

const loading = ref(false);

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "title",
      ellipsis: true,
      width: 300,
    },
    {
      title: "Padalinys",
      key: "padalinys.id",
      filter: true,
      filterMultiple: true,
      filterOptionValues: padaliniaiFilterOptionValues,
      filterOptions: padaliniaiFilterOptions,
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
                  route("main.news", {
                    newsString: "naujiena",
                    lang: row.lang,
                    permalink: row.permalink,
                  }),
                  "_blank"
                );
              } else {
                window.open(
                  route("padalinys.news", {
                    lang: row.lang,
                    newsString: "naujiena",
                    permalink: row.permalink,
                    padalinys: row.padalinys.alias,
                  }),
                  "_blank"
                );
              }
            },
          },
          { default: () => "Peržiūrėti" }
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

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("news.edit", { id: row.id }));
    },
  };
};

const pagination = reactive({
  itemCount: props.news.total,
  page: props.news.current_page,
  pageCount: props.news.last_page,
  pageSize: 10,
  showQuickJumper: true,
});

const handlePageChange = (page) => {
  loading.value = true;
  pagination.page = page;
  Inertia.get(
    route("news.index"),
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
    route("news.index"),
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
      // console.log(props.pages);
      pagination.value = {
        itemCount: props.news.total,
        page: 1,
        pageCount: props.news.last_page,
        pageSize: 20,
        showQuickJumper: true,
      };
      loading.value = false;
      // },
    },
  });
}, 500);
</script>
