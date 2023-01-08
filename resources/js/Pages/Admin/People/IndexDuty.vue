<template>
  <IndexPageLayout
    title="Pareigos"
    model-name="duties"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="duties"
  >
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { h } from "vue";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import AsideHeader from "@/Components/AsideHeaders/AsideHeaderContacts.vue";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  duties: PaginatedModels<App.Entities.Duty>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const columns = [
  {
    title: "Pavadinimas",
    key: "name",
    minWidth: 150,
  },
  {
    title: "Tipas",
    key: "type.name",
    width: 150,
  },
  {
    title: "El. paštas",
    key: "email",
    minWidth: 150,
    render(row) {
      return (
        <a href={`mailto:${row.email}`} class="transition hover:text-vusa-red">
          {row.email}
        </a>
      );
    },
  },
  {
    title: "Institucija",
    key: "institution.id",
    minWidth: 100,
    render(row: App.Entities.Duty) {
      return h(
        "a",
        {
          href: route("institutions.edit", {
            id: row.institution.id,
          }),
          target: "_blank",
          class: "hover:text-vusa-red transition",
        },
        {
          default: () => row.institution.short_name ?? row.institution.name,
        }
      );
    },
  },
];
</script>
