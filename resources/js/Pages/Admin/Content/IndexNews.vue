<template>
  <AdminLayout title="Naujienos" :create-url="route('news.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="title" />
      <IndexDataTable
        :model="news"
        :columns="columns"
        @update-filters-value="padaliniaiFilterOptionValues = $event"
      />
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { DataTableColumns, NButton } from "naive-ui";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { h, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "@/components/Admin/Headers/AsideHeaderContent.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import route from "ziggy-js";

defineProps<{
  news: PaginatedModels<App.Models.News[]>;
}>();

const createColumns = (): DataTableColumns<App.Models.News> => {
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
            href: route("news.edit", { id: row.id }),
            class: "hover:text-vusa-red transition",
          },
          { default: () => row.title }
        );
      },
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

const padaliniaiFilterOptionValues = ref<number[] | null>([]);

padaliniaiFilterOptions.value.unshift({
  label: "VU SA",
  value: 16,
});

const columns = ref(createColumns());
</script>
