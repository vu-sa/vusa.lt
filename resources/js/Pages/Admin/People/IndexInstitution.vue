<template>
  <IndexPageLayout
    title="Institucijos"
    model-name="institutions"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="institutions"
    :icon="Icons.INSTITUTION"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { computed, provide, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import type { DataTableColumns, DataTableSortState } from "naive-ui";

import { updateFilters, updateSorters } from "@/Utils/DataTable";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";

defineProps<{
  institutions: PaginatedModels<App.Entities.Institution[]>;
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: true,
  destroy: true,
};

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  name: false,
});

provide("sorters", { sorters, updateSorters });

const filters = ref<Record<string, any>>({
  "padalinys.id": [],
});

provide("filters", { filters, updateFilters });

const columns = computed<DataTableColumns<App.Entities.Institution>>(() => {
  return [
    {
      title() {
        return $t("forms.fields.title");
      },
      key: "name",
      sorter: true,
      sortOrder: sorters.value.name,
      maxWidth: 300,
      ellipsis: {
        tooltip: true,
      },
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
      key: "padalinys.id",
      filter: true,
      filterOptionValues: filters.value["padalinys.id"],
      filterOptions: usePage().props.padaliniai.map((padalinys) => {
        return {
          label: padalinys.shortname,
          value: padalinys.id,
        };
      }),
      render(row) {
        return row.padalinys?.shortname;
      },
    },
  ];
});
</script>
