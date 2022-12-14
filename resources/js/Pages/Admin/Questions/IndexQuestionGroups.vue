<template>
  <PageContent title="Klausimų grupės">
    <div class="main-card">
      <IndexSearchInput payload-name="search" />
      <IndexDataTable
        show-route="questionGroups.show"
        destroy-route="questionGroups.destroy"
        :model="questionGroups"
        :columns="columns"
      >
      </IndexDataTable>
    </div>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import IndexDataTable from "@/Components/IndexDataTable.vue";
import IndexSearchInput from "@/Components/IndexSearchInput.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";

defineProps<{
  questionGroups: PaginatedModels<Record<string, any>>;
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
