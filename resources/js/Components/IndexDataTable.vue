<template>
  <NDataTable
    remote
    size="small"
    class="overflow-auto"
    :data="paginatedModels.data"
    :scroll-x="768"
    :max-height="dataTableMaxHeight"
    :columns="columnsWithActions"
    :loading="loading"
    :pagination="pagination"
    pagination-behavior-on-filter="first"
    @update:filters="handleFiltersChange"
    @update:page="handlePageChange"
  >
  </NDataTable>
</template>

<script setup lang="tsx">
import { ArrowForward20Filled, Edit20Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NButtonGroup, NDataTable, NIcon } from "naive-ui";
import { computed, reactive, ref } from "vue";

import type { DataTableColumns } from "naive-ui";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";

const props = defineProps<{
  columns: DataTableColumns<Record<string, any>>;
  paginatedModels: PaginatedModels<Record<string, any>>;
  showRoute?: string;
  editRoute?: string;
  destroyRoute?: string;
}>();

// Append the column array with an actions columns
const columnsWithActions = computed(() => {
  return [
    ...props.columns,
    {
      title: props.editRoute || props.destroyRoute ? "Veiksmai" : null,
      key: "actions",
      width: 175,
      render(row) {
        return (
          <NButtonGroup size="small">
            {props.showRoute ? (
              <NButton
                quaternary
                onClick={() => Inertia.visit(route(props.showRoute, row.id))}
              >
                {{
                  icon: () => <NIcon component={ArrowForward20Filled} />,
                }}
              </NButton>
            ) : null}
            {props.editRoute ? (
              <NButton
                quaternary
                onClick={() => Inertia.visit(route(props.editRoute, row.id))}
              >
                {{ icon: () => <NIcon component={Edit20Filled} /> }}
              </NButton>
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

const emit = defineEmits<{
  (e: "updatePaginationPage", id: number): void;
  (e: "updateFiltersValue", value: number[]): void;
}>();

const loading = ref(false);
const padaliniaiFilters = ref<number[]>([]);

const pagination = reactive({
  itemCount: props.paginatedModels.total,
  page: props.paginatedModels.current_page,
  pageCount: props.paginatedModels.last_page,
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
      pagination.itemCount = props.paginatedModels.total;
      pagination.pageCount = props.paginatedModels.last_page;
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

// calculate and update the max height of datatable

const dataTableMaxHeight = ref(window.innerHeight);

const calculateDataTableMaxHeight = () => {
  dataTableMaxHeight.value = window.innerHeight - 350;
  // check if the height is less than 400px
  if (dataTableMaxHeight.value < 425) {
    dataTableMaxHeight.value = 425;
  }
};

calculateDataTableMaxHeight();

// update the height on window resize
window.addEventListener("resize", calculateDataTableMaxHeight);
</script>
