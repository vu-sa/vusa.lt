<template>
  <PageContent title="Narių registracija">
    <div class="main-card">
      <IndexDataTable
        :model="registrationForm"
        :columns="columns"
      ></IndexDataTable>
    </div>
  </PageContent>
</template>

<script lang="ts">
import { h } from "vue";
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { DataTableColumns } from "naive-ui";

import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";

const props = defineProps<{
  registrationForm: PaginatedModels<any>;
}>();

const renderObjects = (object: Record<string, any>) => {
  return Object.entries(object).map(([key, value]) => {
    return h(
      "div",
      { class: "flex flex-row gap-2" },
      {
        default: () => [
          h("span", { class: "font-bold" }, key),
          h("span", value),
        ],
      }
    );
  });
};

const columns: DataTableColumns<any> = [
  // generate columns from row.data
  ...Object.keys(props.registrationForm.data[0].data).map((key) => ({
    title: key,
    key: `data.${key}`,
    minWidth: 150,
  })),
  {
    title: "Užregistravimo data",
    key: "created_at",
    width: 120,
  },
];
</script>
