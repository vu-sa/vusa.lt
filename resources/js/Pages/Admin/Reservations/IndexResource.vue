<template>
  <IndexPageLayout :title="capitalize($tChoice('entities.resource.model', 2))" model-name="resources"
    :icon="Icons.RESOURCE" :can-use-routes="canUseRoutes" :columns="columns" :paginated-models="resources" />
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

import { Icon } from "@iconify/vue";
import { capitalize } from "@/Utils/String";
import { padalinysColumn } from "@/Composables/dataTableColumns";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

const props = defineProps<{
  resources: PaginatedModels<App.Entities.Resource>;
  categories: any;
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
  'padalinys.id': [],
  'category.id': [],
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
    title: "Kategorija",
    key: "category.id",
    filter: true,
    defaultFilterOptionValues: filters.value["category.id"],
    filterOptionValues: filters.value["category_id"],
    filterOptions: props.categories.map((category) => {
      return {
        label: category.name,
        value: category.id,
      };
    }),
    render(row) {
      if (!row.category) {
        return;
      }
      return <div class="flex items-center gap-2"><Icon icon={`fluent:${row.category.icon}`} />{row.category.name}</div>;
    },
  },
  {
    title() {
      return $t("forms.fields.quantity");
    },
    key: "capacity",
    width: 75,
  },
  {
    ...padalinysColumn(filters, usePage().props.padaliniai),
    render(row) {
      return $t(row.padalinys?.shortname);
    },
  },
]);
</script>
