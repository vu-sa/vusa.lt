<template>
  <IndexPageLayout
    title="Baneriai"
    model-name="banners"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="banners"
  >
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { DataTableColumns } from "naive-ui";


import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import AsideHeader from "@/Components/AsideHeaders/AsideHeaderContent.vue";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  banners: PaginatedModels<App.Entities.Banner[]>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const columns: DataTableColumns<App.Entities.Banner> = [
  {
    title: "Pavadinimas",
    key: "title",
    render(row) {
      return (
        <span
          class={row.is_active ? "font-bold text-green-700" : "text-red-700"}
          href={route("banners.edit", { id: row.id })}
        >
          {row.title}
        </span>
      );
    },
  },
  {
    title: "Padalinys",
    key: "padalinys.shortname",
  },
];
</script>
