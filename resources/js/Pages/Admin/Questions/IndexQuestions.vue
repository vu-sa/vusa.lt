<template>
  <PageContent title="Klausimai">
    <div class="main-card">
      <IndexSearchInput payload-name="search" />
      <IndexDataTable
        show-route="questions.show"
        destroy-route="questions.destroy"
        :model="questions"
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
  questions: PaginatedModels<App.Models.Question[]>;
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
