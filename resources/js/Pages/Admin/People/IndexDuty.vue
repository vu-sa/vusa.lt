<template>
  <IndexPageLayout :title="capitalize($tChoice('entities.duty.model', 2))" model-name="duties"
    :can-use-routes="canUseRoutes" :columns="columns" :paginated-models="duties" :icon="Icons.DUTY" />
</template>

<script setup lang="tsx">
import {
  type DataTableColumns,
  type DataTableRowKey,
  type DataTableSortState,
  NButton,
  NEllipsis,
  NIcon,
  NTag,
} from "naive-ui";
import { computed, provide, ref } from "vue";

import { capitalize } from "@/Utils/String";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  duties: PaginatedModels<App.Entities.Duty>;
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: true,
  destroy: false,
};

const checkedRowKeys = ref<DataTableRowKey[]>([]);

provide("checkedRowKeys", checkedRowKeys);

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  name: false,
});

provide("sorters", sorters);

const columns = computed<DataTableColumns<App.Entities.Duty>>(() => [
  {
    title: "Pavadinimas",
    key: "name",
    sorter: true,
    sortOrder: sorters.value.name,
    minWidth: 150,
  },
  {
    title: "El. paštas",
    key: "email",
    minWidth: 150,
    render(row) {
      return (
        <a href={`mailto:${row.email}`} class="transition hover:text-vusa-red">
          {row.email}
        </a>
      );
    },
  },
  {
    title: "Institucija",
    key: "institution.id",
    minWidth: 100,
    render(row: App.Entities.Duty) {
      return row.institution ? (
        <a
          href={route("institutions.edit", {
            id: row.institution.id,
          })}
          target="_blank"
          class="transition hover:text-vusa-red"
        >
          <NButton round size="tiny" tertiary>
            {{
              default: (
                <NEllipsis style="max-width: 150px">
                  {row.institution?.short_name ?? row.institution?.name}
                </NEllipsis>
              ),
              icon: <NIcon component={Icons.INSTITUTION}></NIcon>,
            }}
          </NButton>
        </a>
      ) : null;
    },
  },
  {
    title: "Tipai",
    key: "types",
    render(row: App.Entities.Duty) {
      return row.types?.map((type) => (
        <NTag class="mr-2 last:mr-0" size="tiny" round>
          {type.title}
        </NTag>
      ));
    },
  },
]);
</script>
