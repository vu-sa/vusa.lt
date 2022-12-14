<template>
  <PageContent title="Renginiai" :create-url="route('calendar.create')">
    <div class="main-card">
      <IndexSearchInput payload-name="title" />
      <IndexDataTable
        edit-route="calendar.edit"
        :model="calendar"
        :columns="columns"
      />
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
import { ref } from "vue";
import IndexDataTable from "@/Components/IndexDataTable.vue";
import IndexSearchInput from "@/Components/IndexSearchInput.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import route from "ziggy-js";

defineProps<{
  calendar: PaginatedModels<App.Models.Calendar[]>;
}>();

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "title",
    },
    {
      title: "Data",
      key: "date",
    },
    {
      title: "Padalinys",
      key: "padalinys.shortname",
    },
    {
      title: "Kategorija",
      key: "category.name",
    },
  ];
};

const columns = ref(createColumns());
</script>
