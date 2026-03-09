<template>
  <IndexTablePage ref="indexTablePageRef" v-bind="tableConfig" @filter-changed="handleFilterChange">
    <template #filters>
      <DataTableFilter
        v-model:value="selectedStatuses"
        :options="statusOptions"
        multiple
        @update:value="handleStatusFilterChange"
      >
        {{ capitalize($t("entities.problem.status")) }}
      </DataTableFilter>
      <DataTableFilter
        v-model:value="selectedCategories"
        :options="categoryOptions"
        multiple
        @update:value="handleCategoryFilterChange"
      >
        {{ capitalize($t("entities.problem.categories")) }}
      </DataTableFilter>
      <DataTableFilter
        v-model:value="selectedInstitutions"
        :options="institutionOptions"
        multiple
        @update:value="handleInstitutionFilterChange"
      >
        {{ capitalize($tChoice("entities.institution.model", 2)) }}
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
import { type IndexTablePageProps } from "@/Types/TableConfigTypes";
import Icons from "@/Types/Icons/regular";

const props = defineProps<{
  data: App.Entities.Problem[];
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
  showDeleted?: boolean;
  categories: App.Entities.ProblemCategory[];
  institutions: App.Entities.Institution[];
}>();

const modelName = "problems";
const entityName = "problem";

const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);

const canCreate = computed(() => usePage().props.auth?.can?.create?.problem || false);

const selectedStatuses = ref<string[]>(props.filters?.["status"] || []);
const selectedCategories = ref<number[]>(props.filters?.["category"] || []);
const selectedInstitutions = ref<string[]>(props.filters?.["institution"] || []);

const statusOptions = computed(() => [
  { label: $t("Atvira"), value: "open" },
  { label: $t("Vykdoma"), value: "in_progress" },
  { label: $t("Išspręsta"), value: "resolved" },
]);

const categoryOptions = computed(() =>
  props.categories.map((cat) => ({ label: cat.name as string, value: cat.id }))
);

const institutionOptions = computed(() =>
  props.institutions.map((inst) => ({ label: inst.name as string, value: inst.id }))
);

const statusVariant = (status: string) =>
  ({ open: "destructive", in_progress: "warning", resolved: "success" }[status] ?? "secondary");

const statusLabel = (status: string) =>
  ({ open: $t("Atvira"), in_progress: $t("Vykdoma"), resolved: $t("Išspręsta") }[status] ?? status);

const columns = computed<ColumnDef<App.Entities.Problem, any>[]>(() => [
  {
    accessorKey: "title",
    header: () => capitalize($t("entities.problem.title")),
    cell: ({ row }) => (
      <Link href={route("problems.edit", row.original.id)} class="font-medium hover:underline">
        {row.original.title}
      </Link>
    ),
    enableSorting: true,
    size: 250,
  },
  {
    accessorKey: "status",
    header: () => capitalize($t("entities.problem.status")),
    cell: ({ row }) => (
      <Badge variant={statusVariant(row.original.status)}>
        {statusLabel(row.original.status)}
      </Badge>
    ),
    enableSorting: true,
    size: 120,
  },
  {
    accessorKey: "occurred_at",
    header: () => capitalize($t("entities.problem.occurred_at")),
    cell: ({ row }) =>
      row.original.occurred_at
        ? new Date(row.original.occurred_at).toLocaleDateString("lt-LT")
        : "—",
    enableSorting: true,
    size: 120,
  },
  {
    accessorKey: "resolved_at",
    header: () => capitalize($t("entities.problem.resolved_at")),
    cell: ({ row }) =>
      row.original.resolved_at
        ? new Date(row.original.resolved_at).toLocaleDateString("lt-LT")
        : "—",
    size: 120,
  },
  {
    id: "responsible_user",
    header: () => capitalize($t("entities.problem.responsible_user")),
    cell: ({ row }) => row.original.responsibleUser?.name ?? "—",
    size: 160,
  },
  {
    id: "categories",
    header: () => capitalize($t("entities.problem.categories")),
    cell: ({ row }) => {
      const cats = row.original.categories ?? [];
      if (cats.length === 0) return <span class="text-muted-foreground">—</span>;
      return (
        <div class="flex flex-wrap gap-1">
          {cats.slice(0, 2).map((cat) => (
            <Badge key={cat.id} variant="outline" class="text-xs">{cat.name}</Badge>
          ))}
          {cats.length > 2 && (
            <Badge variant="outline" class="text-xs">+{cats.length - 2}</Badge>
          )}
        </div>
      );
    },
    size: 180,
  },
  {
    id: "tenant",
    header: () => capitalize($tChoice("entities.tenant.model", 1)),
    cell: ({ row }) => row.original.tenant?.shortname ?? "—",
    size: 100,
  },
  createStandardActionsColumn<App.Entities.Problem>(modelName, {
    canEdit: true,
    canDelete: true,
    canRestore: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Problem>>(() => ({
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
  allowToggleDeleted: true,
  showDeleted: props.showDeleted,
  headerTitle: capitalize($tChoice("entities.problem.model", 2)),
  icon: Icons.PROBLEM,
  createRoute: canCreate.value ? route("problems.create") : undefined,
  canCreate: canCreate.value,
}));

const handleStatusFilterChange = (values: string[]) => {
  indexTablePageRef.value?.updateFilter("status", values);
};

const handleCategoryFilterChange = (values: number[]) => {
  indexTablePageRef.value?.updateFilter("category", values);
};

const handleInstitutionFilterChange = (values: string[]) => {
  indexTablePageRef.value?.updateFilter("institution", values);
};

const handleFilterChange = (filterKey: string, value: any) => {
  if (filterKey === "status") selectedStatuses.value = value;
  if (filterKey === "category") selectedCategories.value = value;
  if (filterKey === "institution") selectedInstitutions.value = value;
};

watch(
  () => props.filters,
  (newFilters) => {
    if (!newFilters) return;
    if (newFilters["status"] !== undefined) selectedStatuses.value = newFilters["status"];
    if (newFilters["category"] !== undefined) selectedCategories.value = newFilters["category"];
    if (newFilters["institution"] !== undefined) selectedInstitutions.value = newFilters["institution"];
  },
  { deep: true }
);
</script>
