<template>
  <AdminLayout title="Pareigos" :create-url="route('duties.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="title" />
      <IndexDataTable :model="duties" :columns="columns" />
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { h, ref } from "vue";
import route from "ziggy-js";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";

import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";

defineProps<{
  duties: PaginatedModels<App.Models.Duty[]>;
}>();

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "name",
      width: 300,
      ellipsis: {
        tooltip: true,
      },
      render(row: App.Models.Duty) {
        return h(
          Link,
          {
            href: route("duties.edit", { id: row.id }),
            class: "hover:text-vusa-red transition",
          },
          { default: () => row.name }
        );
      },
    },
    {
      title: "Tipas",
      key: "type.name",
    },
    {
      title: "El. paštas",
      key: "email",
    },
    {
      title: "Institucija",
      key: "institution.id",
      render(row: App.Models.Duty) {
        return row.institution.short_name ?? row.institution.name;
      },
    },
  ];
};

const columns = ref(createColumns());
</script>
