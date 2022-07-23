<template>
  <AdminLayout title="Pareigos" :create-url="route('duties.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="title" />
      <IndexDataTable
        edit-route="duties.edit"
        :model="duties"
        :columns="columns"
      />
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { h, ref } from "vue";
import route from "ziggy-js";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "@/components/Admin/Headers/AsideHeaderContacts.vue";

import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";

defineProps<{
  duties: PaginatedModels<App.Models.Duty>;
}>();

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "name",
      minWidth: 150,
    },
    {
      title: "Tipas",
      key: "type.name",
      width: 150,
    },
    {
      title: "El. paštas",
      key: "email",
      minWidth: 150,
      render(row) {
        return h(
          "a",
          {
            href: `mailto:${row.email}`,
            class: "hover:text-vusa-red transition",
          },
          { default: () => row.email }
        );
      },
    },
    {
      title: "Institucija",
      key: "institution.id",
      minWidth: 100,
      render(row: App.Models.Duty) {
        return h(
          "a",
          {
            href: route("dutyInstitutions.edit", {
              id: row.institution.id,
            }),
            target: "_blank",
            class: "hover:text-vusa-red transition",
          },
          {
            default: () => row.institution.short_name ?? row.institution.name,
          }
        );
      },
    },
  ];
};

const columns = ref(createColumns());
</script>
