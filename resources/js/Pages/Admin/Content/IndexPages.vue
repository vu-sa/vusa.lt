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
        edit-route="pages.edit"
        destroy-route="pages.destroy"
        @update-filters-value="padaliniaiFilterOptionValues = $event"
      />
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { DataTableColumns } from "naive-ui";
import { h, ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";

import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import AsideHeader from "@/Components/Admin/Headers/AsideHeaderContent.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import PreviewModelButton from "@/Components/Admin/Buttons/PreviewModelButton.vue";
import route from "ziggy-js";

defineProps<{
  pages: PaginatedModels<App.Models.Page[]>;
}>();

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

const columns: DataTableColumns<App.Models.News> = [
  {
    title: "Pavadinimas",
    key: "title",
    minWidth: 200,
  },
  {
    // title: "Nuoroda",
    key: "permalink",
    // ellipsis: true,
    width: 55,
    render(row) {
      return h(PreviewModelButton, {
        mainRoute: "main.page",
        padalinysRoute: "padalinys.page",
        mainProps: {
          lang: row.lang,
          permalink: row.permalink,
        },
        padalinysProps: {
          lang: row.lang,
          permalink: row.permalink,
          padalinys: row.padalinys?.alias,
        },
        padalinysShortname: row.padalinys?.shortname,
      });
    },
  },
  {
    title: "Padalinys",
    key: "padalinys.id",
    width: 150,
    ellipsis: {
      tooltip: true,
    },
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
    minWidth: 100,
    ellipsis: {
      tooltip: true,
    },
  },
];
</script>
