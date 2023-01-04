<template>
  <PageContent title="Klausimai">
    <div class="main-card">
      <IndexSearchInput payload-name="search" />
      <IndexDataTable
        show-route="matters.show"
        destroy-route="matters.destroy"
        :model="matters"
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
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  matters: PaginatedModels<App.Models.InstitutionMatter[]>;
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
];
</script>
