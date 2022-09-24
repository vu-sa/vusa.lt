<template>
  <NDataTable
    remote
    size="small"
    class="overflow-auto"
    :data="model.data"
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

<script setup lang="ts">
import { DataTableColumns, NButton, NDataTable, NIcon } from "naive-ui";
import { Edit20Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { computed, h, reactive, ref } from "vue";
import route from "ziggy-js";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";

const props = defineProps<{
  columns: DataTableColumns<any>;
  model: PaginatedModels<any[]>;
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
      width: 100,
      render(row) {
        return h(
          "div",
          {
            class: "flex gap-1",
          },
          {
            default: () => [
              props.editRoute
                ? h(
                    NButton,
                    {
                      size: "small",
                      onClick: () => {
                        Inertia.get(route(props.editRoute, row.id));
                      },
                    },
                    {
                      icon: () =>
                        h(NIcon, {
                          component: Edit20Filled,
                        }),
                    }
                  )
                : null,

              // conditionally render the destroy button
              [
                props.destroyRoute
                  ? h(DeleteModelButton, {
                      size: "small",
                      form: row,
                      modelRoute: props.destroyRoute,
                    })
                  : null,
              ],
            ],
          }
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

// calculate and update the max height of datatable
const dataTableMaxHeight = ref(window.innerHeight - 380);

// update the height on window resize
window.addEventListener("resize", () => {
  dataTableMaxHeight.value = window.innerHeight - 380;
  // check if the height is less than 400px
  if (dataTableMaxHeight.value < 400) {
    dataTableMaxHeight.value = 400;
  }
});
</script>
