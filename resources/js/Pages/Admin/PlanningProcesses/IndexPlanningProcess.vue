<template>
  <IndexTablePage ref="indexTablePageRef" v-bind="tableConfig" @filter-changed="handleFilterChange">
    <template #filters>
      <DataTableFilter
        v-model:value="selectedStages"
        :options="stageOptions"
        multiple
        @update:value="handleStageFilterChange"
      >
        {{ capitalize($t("entities.planningProcess.current_stage")) }}
      </DataTableFilter>
      <DataTableFilter
        v-model:value="selectedTenants"
        :options="tenantOptions"
        multiple
        @update:value="handleTenantFilterChange"
      >
        {{ capitalize($tChoice("entities.tenant.model", 1)) }}
      </DataTableFilter>
      <DataTableFilter
        v-model:value="selectedYears"
        :options="yearOptions"
        multiple
        @update:value="handleYearFilterChange"
      >
        {{ capitalize($t("entities.planningProcess.academic_year")) }}
      </DataTableFilter>
    </template>
  </IndexTablePage>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { type ColumnDef } from "@tanstack/vue-table";
import { computed, ref, watch, capitalize } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import DataTableFilter from "@/Components/ui/data-table/DataTableFilter.vue";
import { Badge } from "@/Components/ui/badge";
import { createStandardActionsColumn } from "@/Composables/useTableActions";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import Icons from "@/Types/Icons/regular";
import { type IndexTablePageProps } from "@/Types/TableConfigTypes";
import CalendarLtr24Regular from "~icons/fluent/calendar-ltr24-regular";

usePageBreadcrumbs(BreadcrumbHelpers.adminIndex($tChoice("entities.planningProcess.model", 2), Icons.PLANNING_PROCESS));

const props = defineProps<{
  data: App.Entities.PlanningProcess[];
  meta: {
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
    from: number;
    to: number;
  };
  filters?: Record<string, any>;
  sorting?: { id: string; desc: boolean }[];
  tenants: App.Entities.Tenant[];
}>();

const modelName = "planningProcesses";
const entityName = "planningProcess";

const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);

const canCreate = computed(() => usePage().props.auth?.can?.create?.planningProcess || false);

const selectedStages = ref<number[]>(props.filters?.["current_stage"] || []);
const selectedTenants = ref<number[]>(props.filters?.["tenant_id"] || []);
const selectedYears = ref<number[]>(props.filters?.["academic_year_start"] || []);

const currentYear = new Date().getFullYear();
const yearOptions = computed(() =>
  Array.from({ length: 6 }, (_, i) => currentYear - 3 + i).map((year) => ({
    label: `${year}–${year + 1}`,
    value: year,
  }))
);

const stageOptions = computed(() => [
  ...[1, 2, 3, 4, 5].map((stage) => ({ label: `${$t("Etapas")} ${stage}`, value: stage })),
  { label: $t("Užbaigta"), value: 6 },
]);

const tenantOptions = computed(() =>
  props.tenants.map((t) => ({ label: t.shortname as string, value: t.id }))
);

const stageVariant = (stage: number) =>
  (["secondary", "secondary", "warning", "warning", "success"] as const)[stage - 1] ?? "secondary";

const columns = computed<ColumnDef<App.Entities.PlanningProcess, any>[]>(() => [
  {
    id: "tenant",
    header: () => capitalize($tChoice("entities.tenant.model", 1)),
    cell: ({ row }) => (
      <Link href={route("planningProcesses.show", row.original.id)} class="font-medium hover:underline">
        {row.original.tenant?.shortname ?? "—"}
      </Link>
    ),
    size: 160,
  },
  {
    accessorKey: "academic_year_start",
    header: () => capitalize($t("entities.planningProcess.academic_year")),
    cell: ({ row }) => {
      const year = row.original.academic_year_start;
      return year ? `${year}–${year + 1}` : "—";
    },
    enableSorting: true,
    size: 120,
  },
  {
    accessorKey: "current_stage",
    header: () => capitalize($t("entities.planningProcess.current_stage")),
    cell: ({ row }) => (
      row.original.current_stage > 5
        ? <Badge variant="success">{$t("Užbaigta")}</Badge>
        : <Badge variant={stageVariant(row.original.current_stage)}>
            {$t("Etapas")} {row.original.current_stage}
          </Badge>
    ),
    enableSorting: true,
    size: 120,
  },
  {
    id: "moderator",
    header: () => capitalize($t("entities.planningProcess.moderator")),
    cell: ({ row }) => row.original.moderator?.name ?? <span class="text-muted-foreground">—</span>,
    size: 160,
  },
  {
    id: "tip_status",
    header: () => "TĮP",
    cell: ({ row }) => (
      row.original.tip_approved_at
        ? <Badge variant="success">{$t("Patvirtinta")}</Badge>
        : row.original.tip_document_url
          ? <Badge variant="warning">{$t("Laukia")}</Badge>
          : <span class="text-muted-foreground">—</span>
    ),
    size: 100,
  },
  {
    id: "mvp_status",
    header: () => "MVP",
    cell: ({ row }) => (
      row.original.mvp_approved_at
        ? <Badge variant="success">{$t("Patvirtinta")}</Badge>
        : row.original.mvp_document_url
          ? <Badge variant="warning">{$t("Laukia")}</Badge>
          : <span class="text-muted-foreground">—</span>
    ),
    size: 100,
  },
  createStandardActionsColumn<App.Entities.PlanningProcess>(modelName, {
    canEdit: false,
    canDelete: true,
    canRestore: false,
    showRoute: (row) => route("planningProcesses.show", row.id),
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.PlanningProcess>>(() => ({
  modelName,
  entityName,
  data: props.data,
  columns: columns.value,
  totalCount: props.meta.total,
  initialPage: props.meta.current_page,
  pageSize: props.meta.per_page,
  initialFilters: props.filters,
  initialSorting: props.sorting,
  enableFiltering: true,
  enableColumnVisibility: true,
  allowToggleDeleted: false,
  headerTitle: capitalize($tChoice("entities.planningProcess.model", 2)),
  icon: CalendarLtr24Regular,
  createRoute: canCreate.value ? route("planningProcesses.create") : undefined,
  canCreate: canCreate.value,
}));

const handleStageFilterChange = (values: number[]) => {
  indexTablePageRef.value?.updateFilter("current_stage", values);
};

const handleTenantFilterChange = (values: number[]) => {
  indexTablePageRef.value?.updateFilter("tenant_id", values);
};

const handleYearFilterChange = (values: number[]) => {
  indexTablePageRef.value?.updateFilter("academic_year_start", values);
};

const handleFilterChange = (filterKey: string, value: any) => {
  if (filterKey === "current_stage") selectedStages.value = value;
  if (filterKey === "tenant_id") selectedTenants.value = value;
  if (filterKey === "academic_year_start") selectedYears.value = value;
};

watch(
  () => props.filters,
  (newFilters) => {
    if (!newFilters) return;
    if (newFilters["current_stage"] !== undefined) selectedStages.value = newFilters["current_stage"];
    if (newFilters["tenant_id"] !== undefined) selectedTenants.value = newFilters["tenant_id"];
    if (newFilters["academic_year_start"] !== undefined) selectedYears.value = newFilters["academic_year_start"];
  },
  { deep: true }
);
</script>
