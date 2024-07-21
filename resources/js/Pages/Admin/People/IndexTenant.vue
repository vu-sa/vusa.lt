<template>
  <IndexPageLayout title="Padaliniai" model-name="tenants" :can-use-routes :columns :paginated-models="tenants"
    :icon="Icons.TENANT" />
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import {
  type DataTableColumns,
} from "naive-ui";
import { computed } from "vue";

import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  tenants: PaginatedModels<App.Entities.Tenant[]>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

// ! Don't forget that columns must be computed for the filters to update
const columns = computed<DataTableColumns<App.Entities.Institution>>(() => {
  return [
    {
      title() {
        return $t("forms.fields.fullname");
      },
      key: "fullname",
    },
    {
      title() {
        return $t("forms.fields.shortname");
      },
      key: "shortname",
    },
    {
      key: "alias",
      title: $t("forms.fields.alias"),
      width: 55,
    },
    {
      title: $tChoice("forms.fields.type", 1),
      key: "type",
    },
  ];
});
</script>
