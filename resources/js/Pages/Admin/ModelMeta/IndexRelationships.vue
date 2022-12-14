<template>
  <PageContent title="Ryšiai" :create-url="route('relationships.create')">
    <div class="main-card">
      <IndexSearchInput payload-name="text" />
      <IndexDataTable
        :model="relationships"
        :columns="columns"
        edit-route="relationships.edit"
      />
    </div>
  </PageContent>
</template>

<script setup lang="ts">
import route from "ziggy-js";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import IndexDataTable from "@/Components/IndexDataTable.vue";
import IndexSearchInput from "@/Components/IndexSearchInput.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

const props = defineProps<{
  relationships: PaginatedModels<any>;
}>();

defineOptions({
  layout: AdminLayout,
});

// add columns
const columns = [
  {
    title: "ID",
    key: "id",
  },
  {
    title: "Pavadinimas",
    key: "name",
  },
  {
    title: "Techninė žymė",
    key: "slug",
  },
  {
    title: "Aprašymas",
    key: "description",
  },
];
</script>
