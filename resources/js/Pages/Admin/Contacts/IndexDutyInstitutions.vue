<template>
  <AdminLayout
    title="Institucijos"
    :create-url="route('dutyInstitutions.create')"
  >
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="search" />
      <IndexDataTable :model="dutyInstitutions" :columns="columns">
      </IndexDataTable>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { h, ref } from "vue";
import route from "ziggy-js";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../../../components/Admin/Headers/AsideHeaderContacts.vue";

import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";

defineProps<{
  dutyInstitutions: PaginatedModels<App.Models.DutyInstitution[]>;
}>();

const columns = [
  {
    title: "Pavadinimas",
    key: "name",
    ellipsis: true,
    width: 300,
    render(row: App.Models.DutyInstitution) {
      return h(
        Link,
        {
          href: route("dutyInstitutions.edit", { id: row.id }),
          class: "hover:text-vusa-red transition",
        },
        { default: () => row.name }
      );
    },
  },
  {
    title: "Trumpas pavadinimas",
    key: "short_name",
  },
  {
    title: "Alias",
    key: "alias",
    render(row: App.Models.DutyInstitution) {
      return h(
        "a",
        {
          href: route("contacts.alias", { alias: row.alias }),
          target: "_blank",
          class: "hover:text-vusa-red transition",
        },
        { default: () => row.alias }
      );
    },
  },
  {
    title: "Padalinys",
    key: "padalinys.shortname",
  },
];
</script>
