<template>
  <NDataTable
    remote
    size="small"
    :data="model.data"
    :columns="columns"
    :loading="loading"
    :pagination="pagination"
    pagination-behavior-on-filter="first"
    @update:filters="handleFiltersChange"
    @update:page="handlePageChange"
  >
  </NDataTable>
</template>

<script setup lang="ts">
import { DataTableColumns, NDataTable } from "naive-ui";
import { Inertia } from "@inertiajs/inertia";
import { reactive, ref } from "vue";

const props = defineProps<{
  columns: DataTableColumns<any>;
  model: PaginatedModels<any[]>;
}>();

const emit = defineEmits<{
  (e: "updatePaginationPage", id: number): void;
  (e: "updateFiltersValue", value: number[]): void;
}>();

const loading = ref(false);
const padaliniaiFilters = ref<number[]>([]);

const pagination = reactive({
  itemCount: props.model.total,
  page: props.model.current_page,
  pageCount: props.model.last_page,
  pageSize: 20,
  showQuickJumper: true,
});

const handleChange = (page: number, filters: number[]) => {
  loading.value = true;
  Inertia.reload({
    data: { page: page, padaliniai: filters },
    preserveState: true,
    onSuccess: () => {
      emit("updateFiltersValue", filters);
      pagination.page = page;
      pagination.itemCount = props.model.total;
      pagination.pageCount = props.model.last_page;
      loading.value = false;
    },
  });
};

const handlePageChange = (page: number) => {
  handleChange(page, padaliniaiFilters.value);
};

const handleFiltersChange = (filters) => {
  padaliniaiFilters.value = filters["padalinys.id"];
  handleChange(pagination.page, padaliniaiFilters.value);
};
</script>
