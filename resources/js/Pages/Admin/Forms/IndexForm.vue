<template>
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
  />
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import type { IndexTablePageInstance,
  IndexTablePageProps } from '@/Types/TableConfigTypes';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import {
  createTextColumn,
  createTenantColumn,
  createTimestampColumn,
} from '@/Composables/useDataTableColumns';

type FormRow = App.Entities.Form & {
  can: {
    view: boolean;
    update: boolean;
    delete: boolean;
  };
};

const props = defineProps<{
  forms: {
    data: FormRow[];
    meta: {
      total: number;
      current_page: number;
      per_page: number;
      last_page: number;
      from: number;
      to: number;
    };
  };
  filters?: Record<string, unknown>;
  sorting?: { id: string; desc: boolean }[];
  showDeleted?: boolean;
  deletedCount?: number;
  can: {
    create: boolean;
  };
}>();

const modelName = 'forms';
const entityName = 'form';

const indexTablePageRef = ref<IndexTablePageInstance | null>(null);

const canForceDelete = computed(() => usePage().props.auth?.can?.forceDelete?.form ?? false);

const getRowId = (row: FormRow) => {
  return `form-${row.id}`;
};

const columns = computed(() => [
  createTextColumn<FormRow>('name', {
    title: $t('forms.fields.name'),
    width: 300,
  }),
  createTextColumn<FormRow>('path', {
    title: $t('forms.fields.link'),
    width: 200,
  }),
  createTenantColumn<FormRow>(),
  createTextColumn<FormRow>('registrations_count', {
    title: $t('Registracijos'),
    width: 120,
    enableSorting: false,
  }),
  createTimestampColumn<FormRow>('updated_at', {
    title: $t('Atnaujinta'),
    width: 160,
  }),
  createStandardActionsColumn<FormRow>('forms', {
    canView: row => row.can.view,
    canEdit: row => row.can.update,
    canDelete: row => row.can.delete,
    canRestore: true,
    canForceDelete: canForceDelete.value,
  }),
]);

const tableConfig = computed<IndexTablePageProps<FormRow>>(() => {
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
    // Mirrors IndexFormRequest::$defaultSorting so the header arrow matches the server.
    initialSorting: props.sorting?.length ? props.sorting : [{ id: 'updated_at', desc: true }],
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,
    allowToggleDeleted: true,
    showDeleted: props.showDeleted,
    deletedCount: props.deletedCount,

    headerTitle: $t('Formos'),
    createRoute: route('forms.create'),
    canCreate: props.can.create,
  };
});
</script>
