<template>
  <IndexSearchInput payload-name="text" :has-soft-deletes="hasSoftDeletes" @complete-search="handleCompletedSearch"
    @update:other="handleShowOther" @sweep="sweepSearch" />
  <!-- Dialog provider is used for the delete button -->
  <NDialogProvider>
    <NDataTable remote :data="paginatedModels.data" :columns="columnsWithActions" :loading :pagination="pagination"
      :row-key="(row) => row.id" pagination-behavior-on-filter="first" v-bind="$attrs"
      @update:sorter="handleSorterChange" @update:page="handleChange" @update:filters="handleFiltersChange"
      @update-checked-row-keys="handleCheckedRowKeysChange" />
  </NDialogProvider>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import {
  type DataTableColumns,
  type DataTableFilterState,
  type DataTableRowKey,
  type DataTableSortState,
  NDialogProvider,
} from "naive-ui";
import { type Ref, computed, h, inject, ref } from "vue";
import { router } from "@inertiajs/vue3";

import ActionColumns from "./ActionColumns.vue";
import IndexSearchInput from "./IndexSearchInput.vue";
import type { SortOrder } from "naive-ui/es/data-table/src/interface";

const props = defineProps<{
  columns: DataTableColumns<Record<string, any>>;
  paginatedModels: PaginatedModels<Record<string, any>>;
  modelName: string;
  showRoute?: string;
  duplicateRoute?: string;
  editRoute?: string;
  destroyRoute?: string;
}>();

const updateSorters = (
  sortersRef: Ref<Record<string, boolean | "ascend" | "descend">>,
  sortState: DataTableSortState | undefined,
) => {
  if (sortState === undefined) {
    // reset values to empty array
    Object.keys(sortersRef.value).forEach((key) => {
      sortersRef.value[key] = false;
    });
    return sortersRef.value;
  }

  // update sorters object key if columnKey is equal to key in sorters object
  Object.keys(sortersRef.value).forEach((key) => {
    if (sortState.columnKey === key) {
      sortersRef.value[key] = sortState.order;
    }
  });

  return sortersRef.value;
};

console.log(props.columns)

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

const checkedRowKeys = inject<Ref<DataTableRowKey[]>>(
  "checkedRowKeys",
  ref([]),
);

const handleCheckedRowKeysChange = (rowKeys: DataTableRowKey[]) => {
  checkedRowKeys.value = rowKeys;
};

const handleChange = (page: number) => {
  let encodedFilters = undefined;
  let encodedSorters = undefined;

  if (filters && filters.value) {
    // Use JSON directly instead of base64
    encodedFilters = JSON.stringify(filters.value);
  }

  if (sorters && sorters.value) {
    // Use JSON directly instead of base64
    encodedSorters = JSON.stringify(sorters.value);
  }

  loading.value = true;
  router.reload({
    data: {
      page: page,
      filters: encodedFilters,
      sorting: encodedSorters,
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

  // Use the same format as handleChange for consistency
  router.reload({
    data: {
      page: 1,
      // Pass empty objects with JSON.stringify instead of undefined
      filters: filters ? JSON.stringify({}) : undefined,
      sorting: sorters ? JSON.stringify({}) : undefined,
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
    ...(Array.isArray(props.columns) ? props.columns : props.columns.value),
    {
      title:
        props.showRoute || props.editRoute || props.destroyRoute || props.duplicateRoute
          ? $t("Veiksmai")
          : null,
      key: "actions",
      fixed: "right",
      width: 85,
      render(row: Record<string, unknown> & { id: number | string, deleted_at: string | undefined | null }) {
        return h(ActionColumns, {
          routes: {
            show: props.showRoute,
            edit: props.editRoute,
            duplicate: props.duplicateRoute,
            destroy: props.destroyRoute,
          },
          model: row,
          modelName: props.modelName,
        }
        );
      },
    },
  ];
});
</script>
