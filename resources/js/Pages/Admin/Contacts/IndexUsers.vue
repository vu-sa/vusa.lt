<template>
  <PageContent title="Kontaktai" :create-url="route('users.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="name" />
      <IndexDataTable
        edit-route="users.edit"
        :model="users"
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
import { h } from "vue";

import AsideHeader from "@/Components/AsideHeaders/AsideHeaderContacts.vue";
import IndexDataTable from "@/Components/IndexDataTable.vue";
import IndexSearchInput from "@/Components/IndexSearchInput.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import route from "ziggy-js";

defineProps<{
  users: PaginatedModels<App.Models.User>;
}>();

// Parse paginated user data into columns

const columns = [
  {
    title: "Vardas",
    key: "name",
  },
  {
    title: "El. paštas",
    key: "email",
    render(row: App.Models.User) {
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
    title: "Telefonas",
    key: "phone",
    render(row: App.Models.User) {
      return h(
        "a",
        {
          href: `tel:${row.phone}`,
          class: "hover:text-vusa-red transition",
        },
        { default: () => row.phone }
      );
    },
  },
];
</script>
