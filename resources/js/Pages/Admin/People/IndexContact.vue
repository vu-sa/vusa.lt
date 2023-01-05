<template>
  <PageContent title="Kontaktai" :create-url="route('contacts.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="name" model="contacts" />
      <IndexDataTable
        edit-route="contacts.edit"
        :model="contacts"
        :columns="columns"
      />
    </div>
  </PageContent>
</template>

<script setup lang="tsx">
import route from "ziggy-js";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import AsideHeader from "@/Components/AsideHeaders/AsideHeaderContacts.vue";
import IndexDataTable from "@/Components/IndexDataTable.vue";
import IndexSearchInput from "@/Components/IndexSearchInput.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  contacts: PaginatedModels<App.Models.Contact>;
}>();

// Parse paginated contact data into columns

const columns = [
  {
    title: "Vardas",
    key: "name",
  },
  {
    title: "El. paštas",
    key: "email",
    render(row: App.Models.Contact) {
      return (
        <a href={`mailto:${row.email}`} class="transition hover:text-vusa-red">
          {row.email}
        </a>
      );
    },
  },
  {
    title: "Telefonas",
    key: "phone",
    render(row: App.Models.Contact) {
      return (
        <a href={`mailto:${row.phone}`} class="transition hover:text-vusa-red">
          {row.phone}
        </a>
      );
    },
  },
];
</script>
