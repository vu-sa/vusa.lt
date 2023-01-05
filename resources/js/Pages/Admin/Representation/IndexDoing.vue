<template>
  <PageContent title="Veiksmai">
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

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import IndexDataTable from "@/Components/IndexDataTable.vue";
import IndexSearchInput from "@/Components/IndexSearchInput.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";

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
    title: "Svarstomi klausimai",
    key: "matters",
    render(row) {
      return row.matters.map((matter) => (
        <NTag key={matter.id}>{matter.id}</NTag>
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
