<template>
  <AdminContentPage>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col space-y-2 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center space-x-4">
          <div class="hidden rounded-md bg-primary/10 p-2 text-primary md:block">
            <CalendarLtr24Regular class="h-5 w-5" />
          </div>
          <div>
            <h2 class="text-2xl font-bold tracking-tight">
              {{ capitalize($tChoice("entities.planningProcess.model", 2)) }}
            </h2>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Button v-if="canCreate" as-child variant="outline" class="gap-1.5">
            <Link :href="route('planningDeadlines.edit', activeAcademicYear)">
              <ClockIcon class="h-4 w-4" />
              <span>{{ $t("planning.deadlines") }}</span>
            </Link>
          </Button>
          <Button v-if="canCreate" as-child variant="default" class="gap-1.5">
            <Link :href="route('planningProcesses.create')">
              <PlusCircleIcon class="h-4 w-4" />
              <span>{{ $t("forms.add") }}</span>
            </Link>
          </Button>
        </div>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <Card class="cursor-pointer transition-colors hover:bg-muted/30" @click="clearAllFilters">
          <CardContent class="p-4">
            <div class="flex items-center gap-3">
              <div class="shrink-0 h-9 w-9 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                <LayoutListIcon class="h-4.5 w-4.5 text-primary" />
              </div>
              <div>
                <p class="text-2xl font-bold leading-none">{{ summary.total }}</p>
                <p class="text-xs text-muted-foreground mt-0.5">{{ $t("Iš viso") }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card
          :class="[
            'cursor-pointer transition-colors',
            needsConfirmation ? 'ring-2 ring-amber-400/50 bg-amber-50/50 dark:bg-amber-950/20' : 'hover:bg-muted/30',
          ]"
          @click="toggleNeedsConfirmation"
        >
          <CardContent class="p-4">
            <div class="flex items-center gap-3">
              <div class="shrink-0 h-9 w-9 rounded-lg bg-amber-500/10 dark:bg-amber-500/20 flex items-center justify-center">
                <AlertCircleIcon class="h-4.5 w-4.5 text-amber-600 dark:text-amber-400" />
              </div>
              <div>
                <p class="text-2xl font-bold leading-none">{{ summary.needs_confirmation }}</p>
                <p class="text-xs text-muted-foreground mt-0.5">{{ $t("Laukia patvirtinimo") }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card class="cursor-pointer transition-colors hover:bg-muted/30" @click="filterByStage(6)">
          <CardContent class="p-4">
            <div class="flex items-center gap-3">
              <div class="shrink-0 h-9 w-9 rounded-lg bg-green-500/10 dark:bg-green-500/20 flex items-center justify-center">
                <CheckCircle2Icon class="h-4.5 w-4.5 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <p class="text-2xl font-bold leading-none">{{ summary.finished }}</p>
                <p class="text-xs text-muted-foreground mt-0.5">{{ $t("Užbaigta") }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card class="cursor-pointer transition-colors hover:bg-muted/30" @click="filterNoModerator">
          <CardContent class="p-4">
            <div class="flex items-center gap-3">
              <div class="shrink-0 h-9 w-9 rounded-lg bg-rose-500/10 dark:bg-rose-500/20 flex items-center justify-center">
                <UserXIcon class="h-4.5 w-4.5 text-rose-600 dark:text-rose-400" />
              </div>
              <div>
                <p class="text-2xl font-bold leading-none">{{ summary.no_moderator }}</p>
                <p class="text-xs text-muted-foreground mt-0.5">{{ $t("Be moderatoriaus") }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Stage distribution bar -->
      <Card>
        <CardContent class="p-4">
          <div class="flex items-center justify-between mb-3">
            <p class="text-sm font-medium">{{ $t("Etapų pasiskirstymas") }}</p>
            <p class="text-xs text-muted-foreground">
              {{ summary.in_progress }} {{ $t("vykdoma") }} · {{ summary.finished }} {{ $t("užbaigta") }}
            </p>
          </div>
          <div class="flex h-3 w-full overflow-hidden rounded-full bg-muted">
            <TooltipProvider>
              <Tooltip v-for="(stage, index) in stageBarSegments" :key="index">
                <TooltipTrigger as-child>
                  <button
                    v-if="stage.percent > 0"
                    type="button"
                    class="h-full transition-all hover:opacity-80"
                    :class="stage.color"
                    :style="{ width: stage.percent + '%' }"
                    @click="filterByStage(stage.stageNumber)"
                  />
                </TooltipTrigger>
                <TooltipContent>
                  {{ stage.label }}: {{ stage.count }}
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>
          <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2.5">
            <button
              v-for="stage in stageBarSegments"
              :key="stage.stageNumber"
              type="button"
              class="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors"
              @click="filterByStage(stage.stageNumber)"
            >
              <span class="h-2.5 w-2.5 rounded-full shrink-0" :class="stage.dotColor" />
              {{ stage.label }}
              <span class="font-medium text-foreground">{{ stage.count }}</span>
            </button>
          </div>
        </CardContent>
      </Card>

      <!-- Table -->
      <ServerDataTable
        ref="dataTableRef"
        :model-name="modelName"
        :entity-name="entityName"
        :data="data"
        :columns="columns"
        :total-count="meta.total"
        :initial-page="meta.current_page"
        :page-size="meta.per_page"
        :can-create="false"
        :enable-filtering="true"
        :enable-column-visibility="true"
        :initial-sorting="sorting"
        :initial-filters="filters"
        :allow-toggle-deleted="false"
        @sorting-changed="handleSortingChanged"
        @page-changed="handlePageChanged"
        @filter-changed="handleFilterChanged"
      >
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
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-medium transition-colors',
              needsConfirmation
                ? 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                : 'border-zinc-200 bg-transparent text-muted-foreground hover:bg-muted dark:border-zinc-800',
            ]"
            @click="toggleNeedsConfirmation"
          >
            <AlertCircleIcon class="h-3.5 w-3.5" />
            {{ $t("Reikia patvirtinimo") }}
          </button>
        </template>
      </ServerDataTable>
    </div>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { type ColumnDef } from "@tanstack/vue-table";
import { computed, ref, watch, capitalize } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import {
  AlertCircle as AlertCircleIcon,
  CheckCircle2 as CheckCircle2Icon,
  Clock as ClockIcon,
  LayoutList as LayoutListIcon,
  PlusCircle as PlusCircleIcon,
  UserX as UserXIcon,
} from "lucide-vue-next";

import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import ServerDataTable from "@/Components/Tables/ServerDataTable.vue";
import DataTableFilter from "@/Components/ui/data-table/DataTableFilter.vue";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Progress } from "@/Components/ui/progress";
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from "@/Components/ui/tooltip";
import { createStandardActionsColumn } from "@/Composables/useTableActions";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import Icons from "@/Types/Icons/regular";
import CalendarLtr24Regular from "~icons/fluent/calendar-ltr24-regular";

usePageBreadcrumbs(BreadcrumbHelpers.adminIndex($tChoice("entities.planningProcess.model", 2), Icons.PLANNING_PROCESS));

interface SummaryStats {
  total: number;
  finished: number;
  in_progress: number;
  needs_confirmation: number;
  no_moderator: number;
  stage_counts: Record<number, number>;
}

const props = defineProps<{
  data: App.Entities.PlanningProcess[];
  summary: SummaryStats;
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

const activeAcademicYear = computed(() => {
  const now = new Date();
  return now.getMonth() >= 8 ? now.getFullYear() : now.getFullYear() - 1;
});

const modelName = "planningProcesses";
const entityName = "planningProcess";

const dataTableRef = ref<InstanceType<typeof ServerDataTable> | null>(null);

const canCreate = computed(() => usePage().props.auth?.can?.create?.planningProcess || false);

const selectedStages = ref<number[]>(props.filters?.["current_stage"] || []);
const selectedTenants = ref<number[]>(props.filters?.["tenant_id"] || []);
const selectedYears = ref<number[]>(props.filters?.["academic_year_start"] || []);
const needsConfirmation = ref<boolean>(props.filters?.["needs_confirmation"] || false);

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

// Stage distribution bar
const stageBarSegments = computed(() => {
  const sc = props.summary.stage_counts;
  const total = props.summary.total || 1;

  return [
    { stageNumber: 1, label: $t("I. Pasiruošimas"), count: sc[1] ?? 0, percent: ((sc[1] ?? 0) / total) * 100, color: "bg-zinc-400 dark:bg-zinc-500", dotColor: "bg-zinc-400" },
    { stageNumber: 2, label: $t("II. Susitikimai"), count: sc[2] ?? 0, percent: ((sc[2] ?? 0) / total) * 100, color: "bg-blue-400 dark:bg-blue-500", dotColor: "bg-blue-400" },
    { stageNumber: 3, label: $t("III. Dokumentai"), count: sc[3] ?? 0, percent: ((sc[3] ?? 0) / total) * 100, color: "bg-amber-400 dark:bg-amber-500", dotColor: "bg-amber-400" },
    { stageNumber: 4, label: $t("IV. MVT"), count: sc[4] ?? 0, percent: ((sc[4] ?? 0) / total) * 100, color: "bg-purple-400 dark:bg-purple-500", dotColor: "bg-purple-400" },
    { stageNumber: 5, label: $t("V. Monitoringas"), count: sc[5] ?? 0, percent: ((sc[5] ?? 0) / total) * 100, color: "bg-cyan-400 dark:bg-cyan-500", dotColor: "bg-cyan-400" },
    { stageNumber: 6, label: $t("Užbaigta"), count: props.summary.finished, percent: (props.summary.finished / total) * 100, color: "bg-green-500 dark:bg-green-500", dotColor: "bg-green-500" },
  ];
});

// Progress display for each row
const stageProgress = (stage: number) => {
  if (stage > 5) return 100;
  return Math.round(((stage - 1) / 5) * 100);
};

const stageVariant = (stage: number) =>
  (["secondary", "secondary", "warning", "warning", "default"] as const)[stage - 1] ?? "secondary";

const columns = computed<ColumnDef<App.Entities.PlanningProcess, any>[]>(() => [
  {
    id: "tenant",
    header: () => capitalize($tChoice("entities.tenant.model", 1)),
    cell: ({ row }) => (
      <Link href={route("planningProcesses.show", row.original.id)} class="font-medium hover:underline">
        {row.original.tenant?.shortname ?? "—"}
      </Link>
    ),
    size: 140,
  },
  {
    accessorKey: "academic_year_start",
    header: () => capitalize($t("entities.planningProcess.academic_year")),
    cell: ({ row }) => {
      const year = row.original.academic_year_start;
      return <span class="text-muted-foreground">{year ? `${year}–${year + 1}` : "—"}</span>;
    },
    enableSorting: true,
    size: 110,
  },
  {
    accessorKey: "current_stage",
    header: () => capitalize($t("entities.planningProcess.current_stage")),
    cell: ({ row }) => {
      const stage = row.original.current_stage;
      const isComplete = stage > 5;
      const progress = stageProgress(stage);

      return (
        <div class="flex items-center gap-2.5 min-w-[140px]">
          <div class="flex-1">
            <div class="flex h-1.5 w-full overflow-hidden rounded-full bg-muted">
              <div
                class={["h-full rounded-full transition-all", isComplete ? "bg-green-500" : "bg-primary"]}
                style={{ width: `${progress}%` }}
              />
            </div>
          </div>
          {isComplete
            ? <Badge variant="success" class="shrink-0 text-xs">{$t("Užbaigta")}</Badge>
            : <span class="text-xs text-muted-foreground shrink-0 tabular-nums">{stage}/5</span>
          }
        </div>
      );
    },
    enableSorting: true,
    size: 180,
  },
  {
    id: "needs_confirmation",
    header: () => "",
    cell: ({ row }) => (
      (row.original as any).needs_confirmation
        ? <TooltipProvider>
            <Tooltip>
              <TooltipTrigger>
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                  <AlertCircleIcon class="h-3 w-3" />
                  {$t("Reikia patvirtinimo")}
                </span>
              </TooltipTrigger>
              <TooltipContent>
                {$t("Koordinatorius turi patvirtinti tikslą arba dokumentą")}
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        : null
    ),
    size: 155,
  },
  {
    id: "moderator",
    header: () => capitalize($t("entities.planningProcess.moderator")),
    cell: ({ row }) =>
      row.original.moderator?.name ?? (
        <span class="text-rose-500 dark:text-rose-400 text-xs italic">{$t("Nepriskirtas")}</span>
      ),
    size: 150,
  },
  {
    id: "documents",
    header: () => $t("Dokumentai"),
    cell: ({ row }) => {
      const tip = row.original.tip_approved_at;
      const mvp = row.original.mvp_approved_at;
      const tipUrl = row.original.tip_document_url;
      const mvpUrl = row.original.mvp_document_url;

      return (
        <div class="flex items-center gap-1.5">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger>
                <span class={[
                  "inline-flex h-5 items-center rounded px-1.5 text-[10px] font-semibold tracking-wider",
                  tip
                    ? "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"
                    : tipUrl
                      ? "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                      : "bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500",
                ]}>
                  TĮP
                </span>
              </TooltipTrigger>
              <TooltipContent>
                {tip ? $t("Patvirtinta") : tipUrl ? $t("Laukia patvirtinimo") : $t("Neįkelta")}
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger>
                <span class={[
                  "inline-flex h-5 items-center rounded px-1.5 text-[10px] font-semibold tracking-wider",
                  mvp
                    ? "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"
                    : mvpUrl
                      ? "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                      : "bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500",
                ]}>
                  MVP
                </span>
              </TooltipTrigger>
              <TooltipContent>
                {mvp ? $t("Patvirtinta") : mvpUrl ? $t("Laukia patvirtinimo") : $t("Neįkelta")}
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>
      );
    },
    size: 110,
  },
  createStandardActionsColumn<App.Entities.PlanningProcess>(modelName, {
    canEdit: false,
    canDelete: true,
    canRestore: false,
    showRoute: (row) => route("planningProcesses.show", row.id),
  }),
]);

// Filter handlers
const handleStageFilterChange = (values: number[]) => {
  dataTableRef.value?.updateFilter("current_stage", values);
};

const handleTenantFilterChange = (values: number[]) => {
  dataTableRef.value?.updateFilter("tenant_id", values);
};

const handleYearFilterChange = (values: number[]) => {
  dataTableRef.value?.updateFilter("academic_year_start", values);
};

const toggleNeedsConfirmation = () => {
  needsConfirmation.value = !needsConfirmation.value;
  dataTableRef.value?.updateFilter("needs_confirmation", needsConfirmation.value || undefined);
};

const filterByStage = (stage: number) => {
  selectedStages.value = [stage];
  needsConfirmation.value = false;
  dataTableRef.value?.updateFilter("current_stage", [stage]);
  dataTableRef.value?.updateFilter("needs_confirmation", undefined);
};

const filterNoModerator = () => {
  // No dedicated backend filter for this, but we can clear other filters
  // and let the user see all — the "Nepriskirtas" label in the moderator column makes it visible
  clearAllFilters();
};

const clearAllFilters = () => {
  selectedStages.value = [];
  selectedTenants.value = [];
  selectedYears.value = [];
  needsConfirmation.value = false;
  dataTableRef.value?.updateFilter("current_stage", []);
  dataTableRef.value?.updateFilter("tenant_id", []);
  dataTableRef.value?.updateFilter("academic_year_start", []);
  dataTableRef.value?.updateFilter("needs_confirmation", undefined);
};

const handleSortingChanged = () => {};
const handlePageChanged = () => {};

const handleFilterChanged = (filterKey: string, value: any) => {
  if (filterKey === "current_stage") selectedStages.value = value;
  if (filterKey === "tenant_id") selectedTenants.value = value;
  if (filterKey === "academic_year_start") selectedYears.value = value;
  if (filterKey === "needs_confirmation") needsConfirmation.value = !!value;
};

watch(
  () => props.filters,
  (newFilters) => {
    if (!newFilters) return;
    if (newFilters["current_stage"] !== undefined) selectedStages.value = newFilters["current_stage"];
    if (newFilters["tenant_id"] !== undefined) selectedTenants.value = newFilters["tenant_id"];
    if (newFilters["academic_year_start"] !== undefined) selectedYears.value = newFilters["academic_year_start"];
    if (newFilters["needs_confirmation"] !== undefined) needsConfirmation.value = !!newFilters["needs_confirmation"];
  },
  { deep: true }
);
</script>
