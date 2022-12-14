<template>
  <PageContent
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
        show-route="dutyInstitutions.show"
        :model="dutyInstitutions"
        :columns="columns"
      >
      </IndexDataTable>
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
import { h } from "vue";
import route from "ziggy-js";

import AsideHeader from "@/Components/AsideHeaders/AsideHeaderContacts.vue";
import IndexDataTable from "@/Components/IndexDataTable.vue";
import IndexSearchInput from "@/Components/IndexSearchInput.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";

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
