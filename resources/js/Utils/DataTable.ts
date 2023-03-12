import type { DataTableFilterState, DataTableSortState } from "naive-ui";
import type { Ref } from "vue";

export const updateSorters = (
  sortersRef: Ref<Record<string, boolean | "ascend" | "descend">>,
  sortState: DataTableSortState | undefined
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

export const updateFilters = (
  filterRef: Ref<Record<string, any>>,
  filterState: DataTableFilterState | undefined
) => {
  if (filterState === undefined) {
    // reset values to empty array
    Object.keys(filterRef.value).forEach((key) => {
      filterRef.value[key] = [];
    });
    return filterRef.value;
  }

  // update filters object key if columnKey is equal to key in filters object
  Object.keys(filterRef.value).forEach((key) => {
    Object.keys(filterState).forEach((filterKey) => {
      if (filterKey === key) {
        filterRef.value[key] = filterState[filterKey];
      } else {
        filterRef.value[key] = [];
      }
    });
  });

  return filterRef.value;
};
