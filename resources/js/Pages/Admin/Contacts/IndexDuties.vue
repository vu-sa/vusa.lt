<template>
  <PageContent title="Pareigos" :create-url="route('duties.create')">
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
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { h, ref } from "vue";
import route from "ziggy-js";

import AsideHeader from "@/Components/AsideHeaders/AsideHeaderContacts.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

import IndexDataTable from "@/Components/IndexDataTable.vue";
import IndexSearchInput from "@/Components/IndexSearchInput.vue";

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
