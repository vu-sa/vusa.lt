<template>
  <IndexPageLayout
    title="Institucijos"
    model-name="institutions"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="institutions"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
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
    title() {
      return $t("forms.fields.title");
    },
    key: "name",
    minWidth: 200,
  },
  {
    key: "alias",
    width: 55,
    render(row) {
      return (
        <PreviewModelButton
          publicRoute="contacts.alias"
          routeProps={{ alias: row.alias, lang: "lt", padalinys: "www" }}
        />
      );
    },
  },
  {
    title() {
      return $t("forms.fields.short_name");
    },
    key: "short_name",
  },
  {
    title: "Padalinys",
    key: "padalinys.shortname",
  },
];
</script>
