<template>
  <AdminLayout title="Puslapiai">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <NDataTable
      remote
      class="main-card"
      :data="props.pages.data"
      :columns="columns"
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
import { ref, reactive, h } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";

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
          row.title
        );
      },
    },
    {
      title: "Padalinys",
      key: "padalinys",
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
          "Peržiūrėti"
        );
      },
    },
  ];
};

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
</script>
