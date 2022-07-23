<template>
  <AdminLayout title="Baneriai">
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
import { Link } from "@inertiajs/inertia-vue3";
import { h, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../../../components/Admin/Headers/AsideHeaderContent.vue";
import IndexDataTable from "@/Components/Admin/IndexDataTable.vue";
import IndexSearchInput from "@/Components/Admin/IndexSearchInput.vue";
import route from "ziggy-js";

defineProps<{
  banners: PaginatedModels<App.Models.Banner[]>;
}>();

const createColumns = () => {
  return [
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
};

const columns = ref(createColumns());
</script>
