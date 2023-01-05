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
import { DataTableColumns, NButton, NDataTable, NIcon } from "naive-ui";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import { computed, reactive, ref } from "vue";
import route from "ziggy-js";

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
      width: 150,
      render(row) {
        return (
          <div class="flex gap-1">
            {props.showRoute ? (
              <Link href={route(props.showRoute, row.id)}>
                <NButton size="small">
                  {{
                    icon: () => <NIcon component={ArrowForward20Filled} />,
                  }}
                </NButton>
              </Link>
            ) : null}
            {props.editRoute ? (
              <Link href={route(props.editRoute, row.id)}>
                <NButton size="small">
                  {{ icon: () => <NIcon component={Edit20Filled} /> }}
                </NButton>
              </Link>
            ) : null}
            {props.destroyRoute ? (
              <DeleteModelButton
                size="small"
                form={row}
                modelRoute={props.destroyRoute}
              />
            ) : null}
          </div>
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
