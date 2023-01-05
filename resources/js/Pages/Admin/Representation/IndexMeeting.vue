<template>
  <PageContent title="Institucijų posėdžiai">
    <div class="main-card">
      <IndexSearchInput payload-name="search" />
      <IndexDataTable
        show-route="meetings.show"
        destroy-route="meetings.destroy"
        :model="meetings"
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
  meetings: PaginatedModels<App.Models.GoalGroup[]>;
}>();

const columns = [
  {
    title: "Institucijos",
    key: "institutions",
    minWidth: 200,
    render(row) {
      return row.institutions.map((institution) => institution.name).join(", ");
    },
  },
];
</script>
