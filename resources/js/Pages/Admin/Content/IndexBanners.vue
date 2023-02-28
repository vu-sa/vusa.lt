<template>
  <IndexPageLayout
    title="Baneriai"
    model-name="banners"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="banners"
    :icon="Icons.BANNER"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import type { DataTableColumns } from "naive-ui";

import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

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
