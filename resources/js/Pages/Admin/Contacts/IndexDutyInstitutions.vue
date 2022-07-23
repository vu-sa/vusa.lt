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
      <IndexDataTable
        edit-route="dutyInstitutions.edit"
        :model="dutyInstitutions"
        :columns="columns"
      >
      </IndexDataTable>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { h } from "vue";
import route from "ziggy-js";

import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import AsideHeader from "@/Components/Admin/Headers/AsideHeaderContacts.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import PreviewModelButton from "@/Components/Admin/Buttons/PreviewModelButton.vue";

defineProps<{
  dutyInstitutions: PaginatedModels<App.Models.DutyInstitution[]>;
}>();

const columns = [
  {
    title: "Pavadinimas",
    key: "name",
    minWidth: 200,
  },
  {
    key: "alias",
    width: 55,
    render(row) {
      return h(PreviewModelButton, {
        mainRoute: "contacts.category",
        padalinysRoute: "contacts.category",
        mainProps: { alias: row.alias },
        padalinysProps: { alias: row.alias },
        padalinysShortname: row.padalinys?.shortname,
      });
    },
  },
  {
    title: "Trumpas pavadinimas",
    key: "short_name",
  },
  {
    title: "Padalinys",
    key: "padalinys.shortname",
  },
];
</script>
