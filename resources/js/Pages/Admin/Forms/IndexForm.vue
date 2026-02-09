<template>
  <!-- Member Registration Special Section -->
  <div v-if="props.canAccessMemberForm" class="mb-6">
    <Card class="border-primary/20 bg-primary/5">
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <UsersIcon class="h-5 w-5" />
          {{ $t('Member Registrations') }}
        </CardTitle>
        <CardDescription>
          {{ $t('View and manage membership registrations from your tenant(s)') }}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Button as-child class="gap-2">
          <Link :href="route('forms.show', props.memberFormId)">
            <EyeIcon class="h-4 w-4" />
            {{ $t('View Member Registrations') }}
          </Link>
        </Button>
      </CardContent>
    </Card>
  </div>

  <!-- Student Rep Registration Special Section -->
  <div v-if="props.canAccessStudentRepForm" class="mb-6">
    <Card class="border-green-500/20 bg-green-500/5">
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <UserPlusIcon class="h-5 w-5" />
          {{ $t('Student Representative Registrations') }}
        </CardTitle>
        <CardDescription>
          {{ $t('View and manage student representative registrations') }}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Button as-child variant="outline" class="gap-2">
          <Link :href="route('forms.show', props.studentRepFormId)">
            <EyeIcon class="h-4 w-4" />
            {{ $t('View Student Rep Registrations') }}
          </Link>
        </Button>
      </CardContent>
    </Card>
  </div>

  <!-- Regular Forms Table -->
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
  />
</template>

<script setup lang="tsx">
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Users as UsersIcon, Eye as EyeIcon, UserPlus as UserPlusIcon } from 'lucide-vue-next';

import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import {
  createTextColumn,
  createTenantColumn,
} from '@/Utils/DataTableColumns';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';

const props = defineProps<{
  forms: {
    data: App.Entities.Form[];
    meta: {
      total: number;
      current_page: number;
      per_page: number;
      last_page: number;
      from: number;
      to: number;
    };
  };
  filters?: Record<string, any>;
  sorting?: { id: string; desc: boolean }[];
  memberFormId?: string;
  canAccessMemberForm?: boolean;
  studentRepFormId?: string;
  canAccessStudentRepForm?: boolean;
}>();

const modelName = 'forms';
const entityName = 'form';

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.Form) => {
  return `form-${row.id}`;
};

const columns = computed<ColumnDef<App.Entities.Form, any>[]>(() => [
  createTextColumn<App.Entities.Form>('name', {
    title: $t('forms.fields.name'),
    width: 300,
  }),
  createTextColumn<App.Entities.Form>('path', {
    title: $t('Nuoroda'),
    width: 200,
  }),
  createTenantColumn<App.Entities.Form>(),
  createStandardActionsColumn<App.Entities.Form>('forms', {
    canView: true,
    canEdit: true,
    canDelete: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Form>>(() => {
  return {
    modelName,
    entityName,
    data: props.forms.data,
    columns: columns.value,
    getRowId,
    totalCount: props.forms.meta.total,
    initialPage: props.forms.meta.current_page,
    pageSize: props.forms.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: 'Formos',
    createRoute: route('forms.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
