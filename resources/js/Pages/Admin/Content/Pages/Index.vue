<template>
  <AdminLayout title="Puslapiai" :create-url="route('pages.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="title" />
      <IndexDataTable
        :model="pages"
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
import AsideHeader from "../AsideHeader.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import route from "ziggy-js";

defineProps<{
  pages: PaginatedModels<App.Models.Page[]>;
}>();

// console.log(usePage().props.value.padaliniai);

const createColumns = (): DataTableColumns<App.Models.Page> => {
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
            class: "hover:text-vusa-red transition",
          },
          { default: () => row.title }
        );
      },
    },
    {
      title: "Padalinys",
      key: "padalinys.id",
      filterMultiple: true,
      filterOptionValues: padaliniaiFilterOptionValues.value,
      filterOptions: padaliniaiFilterOptions.value,
      filter: true,
      render(row) {
        return row.padalinys.shortname;
      },
    },
    {
      title: "Sukurta",
      key: "created_at",
      // check timestamps which is later than the other
      // sorter: (a, b) => Date.parse(b.created_at) - Date.parse(a.created_at),
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

// TODO: fix event and setting filter option values
const padaliniaiFilterOptionValues = ref<number[] | null>([]);

padaliniaiFilterOptions.value.unshift({
  label: "VU SA",
  value: 16,
});

const columns = createColumns();
</script>
