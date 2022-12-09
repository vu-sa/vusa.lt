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

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import PreviewModelButton from "@/Components/Admin/Buttons/PreviewModelButton.vue";

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
    title: "Institucija",
    key: "institution_id",
  },
  {
    title: "Klausimo grupė",
    key: "question_group_id",
  },
];
</script>
