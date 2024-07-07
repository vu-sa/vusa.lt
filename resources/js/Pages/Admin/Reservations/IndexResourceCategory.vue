<template>
  <IndexPageLayout :title="capitalize($tChoice('entities.resource_category.model', 2))" model-name="resourceCategories"
    :icon="Icons.RESOURCE_CATEGORY" :can-use-routes="canUseRoutes" :columns="columns"
    :paginated-models="resourceCategories" />
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import {
  type DataTableColumns,
} from "naive-ui";
import { computed } from "vue";

import { Icon } from "@iconify/vue";
import { capitalize } from "@/Utils/String";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  resourceCategories: PaginatedModels<App.Entities.ResourceCategory>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const columns = computed<DataTableColumns<App.Entities.ResourceCategory>>(() => [
  {
    title() {
      return $t("forms.fields.title");
    },
    key: "name",
    maxWidth: 300,
    ellipsis: {
      tooltip: true,
    },
  },
  {
    title: "Ikona",
    key: "icon",
    render(row: App.Entities.ResourceCategory) {
      return row.icon ? (
        <div class="flex items-center gap-2">
        <Icon icon={`fluent:${row.icon}`} /><span>{row.icon}</span>
        </div>
      ) : (
          <span class="text-black"></span>
      );
    },
  },
]);
</script>
