<template>
  <IndexPageLayout
    :title="$tChoice('entities.resource.model', 2)"
    model-name="resources"
    :icon="Icons.RESOURCE"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="resources"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import {
  type DataTableColumns,
  type DataTableSortState,
  NImage,
  NImageGroup,
  NSpace,
} from "naive-ui";
import { computed, provide, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import { capitalize } from "@/Utils/String";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  resources: PaginatedModels<App.Entities.Resource>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  name: false,
});

provide("sorters", sorters);

const filters = ref<Record<string, any>>({
  padalinys_id: [],
});

provide("filters", filters);

// add columns
const columns = computed<DataTableColumns<App.Entities.Resource>>(() => [
  {
    type: "expand",
    renderExpand(row) {
      return (
        <section class="flex flex-col gap-2 p-2">
          <NImageGroup>
            <NSpace>
              {row.media?.map((image) => (
                <NImage width="150" src={image.original_url} alt={image.name} />
              ))}
            </NSpace>
          </NImageGroup>
          <div>
            <strong>{$t("forms.fields.description")}</strong>
            <p>{row.description}</p>
          </div>
        </section>
      );
    },
  },
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
    title() {
      return $t("forms.fields.quantity");
    },
    key: "capacity",
  },
  {
    title() {
      return capitalize($tChoice("entities.padalinys.model", 1));
    },
    key: "padalinys_id",
    filter: true,
    filterOptionValues: filters.value["padalinys_id"],
    filterOptions: usePage().props.padaliniai.map((padalinys) => {
      return {
        label: $t(padalinys.shortname),
        value: padalinys.id,
      };
    }),
    render(row) {
      return $t(row.padalinys?.shortname);
    },
  },
]);
</script>
