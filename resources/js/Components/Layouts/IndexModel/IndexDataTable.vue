<template>
  <IndexSearchInput
    payload-name="text"
    @complete-search="handleCompletedSearch"
    @sweep="sweepSearch"
  />
  <NDataTable
    remote
    size="small"
    :data="paginatedModels.data"
    :columns="columnsWithActions"
    :loading="loading"
    :pagination="pagination"
    :row-key="(row) => row.id"
    pagination-behavior-on-filter="first"
    @update:sorter="handleSorterChange"
    @update:page="handleChange"
    @update:filters="handleFiltersChange"
  >
  </NDataTable>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { ArrowForward20Filled, Edit20Filled } from "@vicons/fluent";
import {
  type DataTableFilterState,
  type DataTableSortState,
  NButton,
  NButtonGroup,
  NDataTable,
  NIcon,
} from "naive-ui";
import { computed, inject, ref } from "vue";
import { router } from "@inertiajs/vue3";
import type { DataTableColumns } from "naive-ui";

import { Link } from "@inertiajs/vue3";
import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import IndexSearchInput from "./IndexSearchInput.vue";

const props = defineProps<{
  columns: DataTableColumns<Record<string, any>>;
  paginatedModels: PaginatedModels<Record<string, any>>;
  modelName: string;
  showRoute?: string;
  editRoute?: string;
  destroyRoute?: string;
}>();

const loading = ref(false);
const { sorters, updateSorters } = inject("sorters", {});
const { filters, updateFilters } = inject("filters", {});

const pagination = ref({
  itemCount: props.paginatedModels.total,
  page: props.paginatedModels.current_page,
  pageCount: props.paginatedModels.last_page,
  pageSize: 20,
  showQuickJumper: true,
});

const handleFiltersChange = (state: DataTableFilterState) => {
  filters.value = updateFilters(filters, state);
  handleChange(1);
};

const handleSorterChange = (state: DataTableSortState) => {
  sorters.value = updateSorters(sorters, state);
  handleChange(1);
};

const handleChange = (page: number) => {
  // base64 encode the filters

  let encodedFilters = undefined;
  let encodedSorters = undefined;

  if (filters && filters.value) {
    encodedFilters = btoa(JSON.stringify(filters.value));
  }

  if (sorters && sorters.value) {
    encodedSorters = btoa(JSON.stringify(sorters.value));
  }

  loading.value = true;
  router.reload({
    data: { page: page, filters: encodedFilters, sorters: encodedSorters },
    only: [props.modelName],
    onSuccess: () => {
      pagination.value.page = page;
      pagination.value.itemCount = props.paginatedModels.total;
      pagination.value.pageCount = props.paginatedModels.last_page;
      loading.value = false;
    },
  });
};

const handleCompletedSearch = () => handleChange(1);

const sweepSearch = () => {
  updateFilters(filters, undefined);
  updateSorters(sorters, undefined);

  router.reload({
    data: { page: 1, filters: undefined, sorters: undefined, text: undefined },
    only: [props.modelName],
    onSuccess: () => {
      pagination.value.page = 1;
      pagination.value.itemCount = props.paginatedModels.total;
      pagination.value.pageCount = props.paginatedModels.last_page;
      loading.value = false;
    },
  });
};

// Append the column array with an actions columns
const columnsWithActions = computed(() => {
  return [
    ...props.columns,
    {
      title: props.editRoute || props.destroyRoute ? $t("Veiksmai") : null,
      key: "actions",
      width: 175,
      render(row) {
        return (
          <NButtonGroup size="small">
            {props.showRoute ? (
              <Link href={route(props.showRoute, row.id)}>
                <NButton quaternary>
                  {{
                    icon: () => <NIcon component={ArrowForward20Filled} />,
                  }}
                </NButton>
              </Link>
            ) : null}
            {props.editRoute ? (
              <Link href={route(props.editRoute, row.id)}>
                <NButton quaternary>
                  {{ icon: () => <NIcon component={Edit20Filled} /> }}
                </NButton>
              </Link>
            ) : null}
            {props.destroyRoute ? (
              <DeleteModelButton form={row} modelRoute={props.destroyRoute} />
            ) : null}
          </NButtonGroup>
        );
      },
    },
  ];
});
</script>
