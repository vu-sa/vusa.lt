<template>
  <IndexSearchInput
    payload-name="text"
    @complete-search="handleCompletedSearch"
    @sweep="tableFilters = undefined"
  />
  <NDataTable
    remote
    size="small"
    :data="paginatedModels.data"
    :columns="columnsWithActions"
    :loading="loading"
    :pagination="pagination"
    pagination-behavior-on-filter="first"
    @update:page="handlePageChange"
    @update:filters="handleFiltersChange"
  >
  </NDataTable>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { ArrowForward20Filled, Edit20Filled } from "@vicons/fluent";
import { NButton, NButtonGroup, NDataTable, NIcon } from "naive-ui";
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import type { DataTableColumns } from "naive-ui";

import { Link } from "@inertiajs/vue3";
import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import IndexSearchInput from "./IndexSearchInput.vue";

const emit = defineEmits<{
  (event: "search:complete", ...args: any[]): void;
}>();

const props = defineProps<{
  columns: DataTableColumns<Record<string, any>>;
  paginatedModels: PaginatedModels<Record<string, any>>;
  modelName: string;
  showRoute?: string;
  editRoute?: string;
  destroyRoute?: string;
}>();

const loading = ref(false);
const tableFilters = ref(undefined);

const pagination = ref({
  itemCount: props.paginatedModels.total,
  page: props.paginatedModels.current_page,
  pageCount: props.paginatedModels.last_page,
  pageSize: 20,
  showQuickJumper: true,
});

const handleFiltersChange = (filters: any) => {
  tableFilters.value = filters;
  handleChange(1);
};

const handleChange = (page: number) => {
  // base64 encode the filters

  let encodedFilters = undefined;
  if (tableFilters.value) {
    encodedFilters = btoa(JSON.stringify(tableFilters.value));
  }

  loading.value = true;
  router.reload({
    data: { page: page, filters: encodedFilters },
    only: [props.modelName],
    onSuccess: () => {
      pagination.value.page = page;
      pagination.value.itemCount = props.paginatedModels.total;
      pagination.value.pageCount = props.paginatedModels.last_page;
      loading.value = false;
      emit("search:complete", tableFilters.value);
    },
  });
};

const handlePageChange = (page: number) => {
  handleChange(page);
};

const handleCompletedSearch = () => {
  handleChange(1);
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
