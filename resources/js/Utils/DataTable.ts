import type { DataTableFilterState, DataTableSortState } from "naive-ui";
import type { Ref } from "vue";

export const updateSorters = (
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
