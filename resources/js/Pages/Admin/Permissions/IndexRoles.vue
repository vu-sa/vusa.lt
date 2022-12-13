<template>
  <PageContent title="Rolės" :create-url="route('roles.create')">
    <div class="main-card">
      <IndexSearchInput payload-name="text" />
      <IndexDataTable
        :model="roles"
        :columns="columns"
        edit-route="roles.edit"
      />
    </div>
  </PageContent>
</template>

<script setup lang="tsx">
import route from "ziggy-js";

import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";

defineOptions({
  layout: AdminLayout,
});

const props = defineProps<{
  roles: PaginatedModels<any>;
}>();

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
    title: "Guard name",
    key: "guard_name",
  },
  {
    title: "Sukurtas",
    key: "created_at",
    render(row) {
      return new Date(row.created_at).toLocaleString("lt-LT");
    },
  },
  {
    title: "Atnaujintas",
    key: "updated_at",
    render(row) {
      return new Date(row.updated_at).toLocaleString("lt-LT");
    },
  },
];
</script>
