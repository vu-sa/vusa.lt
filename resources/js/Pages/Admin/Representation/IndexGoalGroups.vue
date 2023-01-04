<template>
  <PageContent title="Tikslų grupės">
    <div class="main-card">
      <IndexSearchInput payload-name="search" />
      <IndexDataTable
        show-route="goalGroups.show"
        destroy-route="goalGroups.destroy"
        :model="goalGroups"
        :columns="columns"
      >
      </IndexDataTable>
    </div>
  </PageContent>
</template>

<script setup lang="tsx">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import IndexDataTable from "@/Components/IndexDataTable.vue";
import IndexSearchInput from "@/Components/IndexSearchInput.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  goalGroups: PaginatedModels<App.Models.GoalGroup>;
}>();

const columns = [
  {
    title: "Pavadinimas",
    key: "title",
    minWidth: 200,
  },
];
</script>
