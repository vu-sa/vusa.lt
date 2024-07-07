<template>
  <IndexPageLayout title="Institucijos" model-name="institutions" :can-use-routes="canUseRoutes" :columns="columns"
    :paginated-models="institutions" :icon="Icons.INSTITUTION" />
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import {
  type DataTableColumns,
  type DataTableSortState,
  NIcon,
  NTag,
} from "naive-ui";
import { computed, provide, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import { formatStaticTime } from "@/Utils/IntlTime";
import { padalinysColumn } from "@/Composables/dataTableColumns";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import ModelChip from "@/Components/Chips/ModelChip.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";

const props = defineProps<{
  institutions: PaginatedModels<App.Entities.Institution[]>;
  types: App.Entities.Type[];
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: true,
  destroy: true,
};

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  name: false,
});

provide("sorters", sorters);

const filters = ref<Record<string, any>>({
  "padalinys.id": [],
  "types.id": [],
});

provide("filters", filters);

// ! Don't forget that columns must be computed for the filters to update
const columns = computed<DataTableColumns<App.Entities.Institution>>(() => {
  return [
    {
      title() {
        return $t("forms.fields.title");
      },
      key: "name",
      sorter: true,
      sortOrder: sorters.value.name,
      maxWidth: 300,
      ellipsis: {
        tooltip: true,
      },
    },
    {
      key: "alias",
      width: 55,
      render(row) {
        return (
          <PreviewModelButton
            publicRoute="contacts.alias"
            routeProps={{
              institution: row.alias,
              lang: "lt",
              subdomain: "www",
            }}
          />
        );
      },
    },
    {
      ...padalinysColumn(filters, usePage().props.padaliniai),
      render(row) {
        return $t(row.padalinys?.shortname ?? "");
      },
    },
    {
      type: "expand",
      expandable: (rowData) => rowData.meetings.length > 0,
      renderExpand: (rowData) => {
        return (
          <div class="flex flex-wrap items-center gap-2">
            <ModelChip>
              {{
                default: () => "Susitikimai",
                icon: <NIcon component={Icons.MEETING}></NIcon>,
              }}
            </ModelChip>
            {rowData.meetings?.map((meeting) => {
              return (
                <a target="_blank" href={route("meetings.show", meeting.id)}>
                  {formatStaticTime(new Date(meeting.start_time))}
                </a>
              );
            })}
          </div>
        );
      },
    },
    {
      title() {
        return $tChoice("forms.fields.type", 2);
      },
      key: "types.id",
      filter: true,
      filterOptionValues: filters.value["types.id"],
      filterOptions: props.types.map((type) => {
        return {
          label: $t(type.title),
          value: type.id,
        };
      }),
      render(row) {
        return row.types.map((type) => {
          return (
            <NTag size="small" class="mr-2">
              {{
                default: () => type.title,
              }}
            </NTag>
          );
        });
      },
    },
  ];
});
</script>
