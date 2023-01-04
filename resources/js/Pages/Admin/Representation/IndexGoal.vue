<template>
  <PageContent title="Tikslai">
    <div class="main-card">
      <IndexSearchInput payload-name="search" />
      <IndexDataTable
        show-route="goals.show"
        destroy-route="goals.destroy"
        :model="goals"
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
  goals: PaginatedModels<Record<string, any>>;
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
    title: "Tikslo grupė",
    key: "goal_id",
  },
];
</script>
