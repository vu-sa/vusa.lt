<template>
  <IndexPageLayout
    title="Institucijos"
    model-name="institutions"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="institutions"
  >
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import AsideHeader from "@/Components/AsideHeaders/AsideHeaderContacts.vue";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";
import type { DataTableColumns } from "naive-ui";

defineProps<{
  institutions: PaginatedModels<App.Entities.Institution[]>;
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: true,
  destroy: true,
};

const columns: DataTableColumns<App.Entities.Institution> = [
  {
    title: "Pavadinimas",
    key: "name",
    minWidth: 200,
  },
  {
    key: "alias",
    width: 55,
    render(row) {
      return (
        <PreviewModelButton
          mainRoute="contacts.category"
          padalinysRoute="contacts.category"
          mainProps={{ alias: row.alias }}
          padalinysProps={{ alias: row.alias }}
          padalinysShortname={row.padalinys?.shortname}
        />
      );
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
