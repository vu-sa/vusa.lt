<template>
  <IndexPageLayout
    title="Institucijos"
    model-name="institutions"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="institutions"
    :icon="Icons.INSTITUTION"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import {
  type DataTableColumns,
  type DataTableSortState,
  NIcon,
} from "naive-ui";
import { computed, provide, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import { formatStaticTime } from "@/Utils/IntlTime";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import ModelChip from "@/Components/Chips/ModelChip.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";

defineProps<{
  institutions: PaginatedModels<App.Entities.Institution[]>;
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
      key: "alias",
      width: 55,
      render(row) {
        return (
          <PreviewModelButton
            publicRoute="contacts.alias"
            routeProps={{ alias: row.alias, lang: "lt", padalinys: "www" }}
          />
        );
      },
    },
    {
      title() {
        return $t("forms.fields.short_name");
      },
      key: "short_name",
    },
    {
      title: "Padalinys",
      key: "padalinys.id",
      filter: true,
      filterOptionValues: filters.value["padalinys.id"],
      filterOptions: usePage().props.padaliniai.map((padalinys) => {
        return {
          label: padalinys.shortname,
          value: padalinys.id,
        };
      }),
      render(row) {
        return row.padalinys?.shortname;
      },
    },
  ];
});
</script>
