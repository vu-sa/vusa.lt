<template>
  <AdminIndexPage
    model-name="institutions"
    entity-name="institution" 
    :paginated-models="institutions"
    :column-builder="buildColumns"
    :initial-sorters="{ name: false }"
    :initial-filters="{ 'types.id': [] }"
    :icon="Icons.INSTITUTION"
    :can-use-routes="{
      create: true,
      show: true,
      edit: true,
      destroy: true,
      duplicate: false
    }"
  />
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { type DataTableColumns, NIcon, NTag } from "naive-ui";
import { type Ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import { formatStaticTime } from "@/Utils/IntlTime";
import { tenantColumn } from "@/Composables/dataTableColumns";
import { TableFilters } from "@/Composables/useTableState";
import AdminIndexPage from "@/Components/Layouts/IndexModel/AdminIndexPage.vue";
import Icons from "@/Types/Icons/regular";
import ModelChip from "@/Components/Tag/ModelChip.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";

const props = defineProps<{
  institutions: PaginatedModels<App.Entities.Institution[]>;
  types: App.Entities.Type[];
}>();

// Columns builder function that will be passed to AdminIndexPage
const buildColumns = (sorters: Ref<Record<string, any>>, filters: Ref<TableFilters>): DataTableColumns<App.Entities.Institution> => {
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
      ...tenantColumn(filters, usePage().props.tenants),
      render(row) {
        return $t(row.tenant?.shortname ?? "");
      },
    },
    {
      type: "expand",
      expandable: (rowData) => rowData.meetings?.length > 0,
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
        return row.types?.map((type) => {
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
};
</script>
