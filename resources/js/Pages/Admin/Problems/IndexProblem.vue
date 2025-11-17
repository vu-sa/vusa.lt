<template>
  <IndexPageLayout
    :title="capitalize($tChoice('entities.problem.model', 2))"
    model-name="problems"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="problems"
    :icon="Icons.PROBLEM"
  >
    <div class="mb-4 flex items-center gap-2">
      <input
        id="show-my-problems"
        v-model="showMyProblems"
        type="checkbox"
        class="h-4 w-4 rounded border-gray-300 text-vusa-red focus:ring-vusa-red"
        @change="handleToggleMyProblems"
      />
      <label
        for="show-my-problems"
        class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer"
      >
        {{ $t("Rodyti tik mano sukurtas problemas") }}
      </label>
    </div>
  </IndexPageLayout>
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
import { router, usePage } from "@inertiajs/vue3";
import { transChoice as $tChoice } from "laravel-vue-i18n";

import { capitalize } from "@/Utils/String";
import { tenantColumn } from "@/Composables/dataTableColumns";
import Icons from "@/Types/Icons/regular";
import IconsFilled from "@/Types/Icons/filled";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import type { PaginatedModels } from "@/Types/PaginatedModels";

const props = defineProps<{
  problems: PaginatedModels<App.Entities.Problem>;
  categories: Array<App.Entities.ProblemCategory>;
  institutions: Array<App.Entities.Institution>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const checkedRowKeys = ref<DataTableRowKey[]>([]);
provide("checkedRowKeys", checkedRowKeys);

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  title: false,
  status: false,
  occurred_at: false,
});

provide("sorters", sorters);

const filters = ref<Record<string, any>>({
  "tenant.id": [],
  status: [],
  category: [],
  institution: [],
});

provide("filters", filters);

const showMyProblems = ref(false);

const handleToggleMyProblems = () => {
  router.reload({
    data: {
      show_my_problems: showMyProblems.value,
    },
    only: ["problems"],
  });
};

const columns = computed<DataTableColumns<App.Entities.Problem>>(() => [
  {
    title: $tChoice("entities.problem.title", 1),
    key: "title",
    sorter: true,
    sortOrder: sorters.value.title,
    minWidth: 200,
    render(row) {
      return (
        <a
          href={route("problems.edit", row.id)}
          class="transition hover:text-vusa-red font-medium"
        >
          <NEllipsis style="max-width: 250px">{row.title}</NEllipsis>
        </a>
      );
    },
  },
  {
    ...tenantColumn(filters, usePage().props.tenants),
    render(row: App.Entities.Problem) {
      return row.tenant ? (
        <NTag size="small" round>
          {row.tenant.shortname}
        </NTag>
      ) : null;
    },
  },
  {
    title: $tChoice("entities.problem.status", 1),
    key: "status",
    sorter: true,
    sortOrder: sorters.value.status,
    minWidth: 120,
    filter: true,
    filterOptionValues: filters.value["status"],
    filterOptions: [
      { label: "Atvira", value: "open" },
      { label: "Vykdoma", value: "in_progress" },
      { label: "Išspręsta", value: "resolved" },
    ],
    render(row: App.Entities.Problem) {
      const statusConfig = {
        open: {
          label: "Atvira",
          type: "error" as const,
        },
        in_progress: {
          label: "Vykdoma",
          type: "warning" as const,
        },
        resolved: {
          label: "Išspręsta",
          type: "success" as const,
        },
      };
      const config = statusConfig[row.status as keyof typeof statusConfig];
      return (
        <NTag type={config.type} size="small" round>
          {config.label}
        </NTag>
      );
    },
  },
  {
    title: $tChoice("entities.problem.occurred_at", 1),
    key: "occurred_at",
    sorter: true,
    sortOrder: sorters.value.occurred_at,
    minWidth: 110,
    render(row) {
      return row.occurred_at
        ? new Date(row.occurred_at).toLocaleDateString("lt-LT")
        : "-";
    },
  },
  {
    title: $tChoice("entities.problem.resolved_at", 1),
    key: "resolved_at",
    minWidth: 110,
    render(row) {
      return row.resolved_at
        ? new Date(row.resolved_at).toLocaleDateString("lt-LT")
        : "-";
    },
  },
  {
    title: $tChoice("entities.problem.responsible_user", 1),
    key: "responsible_user.name",
    minWidth: 150,
    render(row: App.Entities.Problem) {
      return row.responsible_user ? (
        <NEllipsis style="max-width: 150px">
          {row.responsible_user.name}
        </NEllipsis>
      ) : (
        "-"
      );
    },
  },
  {
    title: $tChoice("entities.problem.categories", 2),
    key: "category",
    minWidth: 150,
    filter: true,
    filterOptionValues: filters.value["category"],
    filterOptions: props.categories.map((cat) => ({
      label: cat.name,
      value: cat.id,
    })),
    render(row: App.Entities.Problem) {
      if (!row.categories || row.categories.length === 0) {
        return "-";
      }

      return (
        <div class="flex flex-wrap gap-1">
          {row.categories.slice(0, 2).map((category, index) => (
            <NTag size="tiny" round key={category.id || index}>
              {category.name}
            </NTag>
          ))}
          {row.categories.length > 2 && (
            <NTag size="tiny" round>
              +{row.categories.length - 2}
            </NTag>
          )}
        </div>
      );
    },
  },
  {
    title: $tChoice("entities.institution.model", 2),
    key: "institution",
    minWidth: 150,
    filter: true,
    filterOptionValues: filters.value["institution"],
    filterOptions: props.institutions.map((inst) => ({
      label: inst.name,
      value: inst.id,
    })),
    render(row: App.Entities.Problem) {
      if (!row.institutions || row.institutions.length === 0) {
        return "-";
      }

      return (
        <div class="flex flex-wrap gap-1">
          {row.institutions.slice(0, 2).map((institution, index) => (
            <NTag size="tiny" round key={institution.id || index}>
              {institution.name}
            </NTag>
          ))}
          {row.institutions.length > 2 && (
            <NTag size="tiny" round>
              +{row.institutions.length - 2}
            </NTag>
          )}
        </div>
      );
    },
  },
]);
</script>
