<template>
  <IndexSearchInput
    payload-name="text"
    :has-soft-deletes="hasSoftDeletes"
    @complete-search="handleCompletedSearch"
    @update:other="handleShowOther"
    @sweep="sweepSearch"
  />
  <NDataTable
    remote
    size="small"
    :data="paginatedModels.data"
    :columns="columnsWithActions"
    :loading="loading"
    :pagination="pagination"
    :row-key="rowKey"
    pagination-behavior-on-filter="first"
    @update:sorter="handleSorterChange"
    @update:page="handleChange"
    @update:filters="handleFiltersChange"
    @update-checked-row-keys="handleCheckedRowKeysChange"
  >
  </NDataTable>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import {
  ArrowCounterclockwise28Regular,
  ArrowForward20Filled,
  Edit20Filled,
} from "@vicons/fluent";
import {
  type DataTableFilterState,
  type DataTableSortState,
  NButton,
  NButtonGroup,
  NDataTable,
  NIcon,
} from "naive-ui";
import { type Ref, computed, inject, ref } from "vue";
import { router } from "@inertiajs/vue3";
import type { DataTableColumns, DataTableRowKey } from "naive-ui";

import { Link } from "@inertiajs/vue3";
import { updateSorters } from "@/Utils/DataTable";
import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import IndexSearchInput from "./IndexSearchInput.vue";
import type { SortOrder } from "naive-ui/es/data-table/src/interface";

const props = defineProps<{
  columns: DataTableColumns<Record<string, any>>;
  paginatedModels: PaginatedModels<Record<string, any>>;
  modelName: string;
  showRoute?: string;
  editRoute?: string;
  destroyRoute?: string;
}>();

const loading = ref(false);
const otherParams = ref<Record<string, boolean | undefined>>({});

const hasSoftDeletes = computed(() => {
  if (props.paginatedModels.data.length === 0) {
    return true;
  }

  return Object.keys(props.paginatedModels.data[0]).includes("deleted_at");
});

const sorters = inject<Ref<Record<string, SortOrder>> | undefined>(
  "sorters",
  undefined,
);

const filters = inject<Ref<Record<string, any>> | undefined>(
  "filters",
  undefined,
);

const pagination = ref({
  itemCount: props.paginatedModels.total,
  page: props.paginatedModels.current_page,
  pageCount: props.paginatedModels.last_page,
  pageSize: props.paginatedModels.per_page,
  showQuickJumper: true,
});

const handleSorterChange = (state: DataTableSortState) => {
  if (sorters !== undefined) {
    sorters.value = updateSorters(sorters, state);
  }

  handleChange(1);
};

const handleFiltersChange = (state: DataTableFilterState) => {
  if (filters !== undefined) {
    filters.value = state;
  }

  handleChange(1);
};

const rowKey = (row: Record<string, any>) => row.id;

const checkedRowKeys = inject<Ref<DataTableRowKey[]>>(
  "checkedRowKeys",
  ref([]),
);

const handleCheckedRowKeysChange = (rowKeys: DataTableRowKey[]) => {
  checkedRowKeys.value = rowKeys;
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
    data: {
      page: page,
      filters: encodedFilters,
      sorters: encodedSorters,
      ...otherParams.value,
    },
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

const handleShowOther = (event: string[]) => {
  // make each otherParams property to false
  Object.keys(otherParams.value).forEach((key) => {
    otherParams.value[key] = false;
  });

  event.forEach((key: string) => {
    otherParams.value[key] = true;
  });

  handleChange(1);
};

const sweepSearch = () => {
  if (filters !== undefined) {
    Object.keys(filters.value).forEach((key) => {
      filters.value[key] = [];
    });
  }

  if (sorters !== undefined) {
    sorters.value = updateSorters(sorters, undefined);
  }

  handleCheckedRowKeysChange([]);

  Object.keys(otherParams.value).forEach((key) => {
    otherParams.value[key] = undefined;
  });

  router.reload({
    data: {
      page: 1,
      filters: undefined,
      sorters: undefined,
      text: undefined,
      ...otherParams.value,
    },
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
      title:
        props.showRoute || props.editRoute || props.destroyRoute
          ? $t("Veiksmai")
          : null,
      key: "actions",
      width: 175,
      render(row) {
        return [undefined, null].includes(row.deleted_at) ? (
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
        ) : (
          <NButtonGroup size="small">
            {/* restore */}
            <NButton
              quaternary
              onClick={() =>
                router.patch(route(`${props.modelName}.restore`, row.id))
              }
            >
              {{
                icon: () => (
                  <NIcon component={ArrowCounterclockwise28Regular} />
                ),
              }}
            </NButton>
          </NButtonGroup>
        );
      },
    },
  ];
});
</script>
