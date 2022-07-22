<template>
  <AdminLayout title="Kontaktai" :create-url="route('users.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="name" />
      <IndexDataTable :model="users" :columns="columns" />
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { h } from "vue";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "@/components/Admin/Headers/AsideHeaderContacts.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import route from "ziggy-js";

defineProps<{
  users: PaginatedModels<App.Models.User>;
}>();

// Parse paginated user data into columns

const columns = [
  {
    title: "Vardas",
    key: "name",
    render(row: App.Models.User) {
      return h(
        Link,
        {
          href: route("users.edit", { id: row.id }),
          class: "hover:text-vusa-red transition",
        },
        { default: () => row.name }
      );
    },
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
