<template>
  <AdminLayout title="Naujienos">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <NDataTable
      remote
      class="main-card"
      size="small"
      :data="props.news.data"
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
import AsideHeader from "../AsideHeader.vue";
import { NDataTable, NButton } from "naive-ui";
import { ref, h, reactive } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";

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
      key: "padalinys_id",
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
          "Peržiūrėti"
        );
      },
    },
  ];
};

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
</script>
