<template>
  <PageContent title="Veiklos">
    <div class="main-card">
      <IndexSearchInput payload-name="search" />
      <IndexDataTable
        show-route="doings.show"
        destroy-route="doings.destroy"
        :model="doings"
        :columns="columns"
      >
      </IndexDataTable>
    </div>
  </PageContent>
</template>

<script setup lang="tsx">
import { NTag } from "naive-ui";

import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import PreviewModelButton from "@/Components/Admin/Buttons/PreviewModelButton.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  doings: PaginatedModels<App.Models.Doing[]>;
}>();

const columns = [
  {
    title: "Pavadinimas",
    key: "title",
    minWidth: 200,
  },
  {
    title: "Data",
    key: "date",
  },
  {
    title: "Statusas",
    key: "status",
  },
  {
    title: "Klausimai",
    key: "questions",
    render(row) {
      return row.questions.map((question) => (
        <NTag key={question.id}>{question.id}</NTag>
      ));
    },
  },
  {
    title: "Tipai",
    key: "types",
    render(row) {
      return row.types.map((type) => <NTag key={type.id}>{type.title}</NTag>);
    },
  },
];
</script>
