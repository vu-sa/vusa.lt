<template>
  <IndexSearchInput
    payload-name="text"
    @complete-search="handleCompletedSearch"
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
  >
  </NDataTable>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { ArrowForward20Filled, Edit20Filled } from "@vicons/fluent";
import { NButton, NButtonGroup, NDataTable, NIcon } from "naive-ui";
import { computed, reactive, ref } from "vue";
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

const handleCompletedSearch = () => {
  handleChange(1);
};

const pagination = reactive({
  itemCount: props.paginatedModels.total,
  page: props.paginatedModels.current_page,
  pageCount: props.paginatedModels.last_page,
  pageSize: 20,
  showQuickJumper: true,
});

const handleChange = (page: number) => {
  loading.value = true;
  router.reload({
    data: { page: page },
    only: [props.modelName],
    onSuccess: () => {
      pagination.page = page;
      pagination.itemCount = props.paginatedModels.total;
      pagination.pageCount = props.paginatedModels.last_page;
      loading.value = false;
    },
  });
};

const handlePageChange = (page: number) => {
  handleChange(page);
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
