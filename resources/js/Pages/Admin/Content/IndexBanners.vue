<template>
  <AdminLayout title="Baneriai" :create-url="route('banners.create')">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <IndexSearchInput payload-name="title" />
      <IndexDataTable
        edit-route="banners.edit"
        :model="banners"
        :columns="columns"
      />
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { h } from "vue";
import AdminLayout from "@/components/Admin/Layouts/AdminLayout.vue";
import AsideHeader from "../../../components/Admin/Headers/AsideHeaderContent.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import route from "ziggy-js";

defineProps<{
  banners: PaginatedModels<App.Models.Banner[]>;
}>();

const columns = [
  {
    title: "Pavadinimas",
    key: "title",
    render(row: App.Models.Banner) {
      return h(
        "span",
        {
          class: row.is_active ? "text-green-700 font-bold" : "text-red-700",
          href: route("banners.edit", { id: row.id }),
        },
        { default: () => row.title }
      );
    },
  },
  {
    title: "Padalinys",
    key: "padalinys.shortname",
  },
];
</script>
