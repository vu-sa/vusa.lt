<template>
  <PageContent title="Baneriai" :create-url="route('banners.create')">
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
import AsideHeader from "@/Components/AsideHeaders/AsideHeaderContent.vue";
import IndexDataTable from "@/Components/IndexDataTable.vue";
import IndexSearchInput from "@/Components/IndexSearchInput.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
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
