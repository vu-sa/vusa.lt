<template>
  <IndexTablePage v-bind="tableConfig" />
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ListChecks } from 'lucide-vue-next';
import { computed, h } from 'vue';

import type { IndexTablePageProps } from '@/Types/TableConfigTypes';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { createTextColumn } from '@/Composables/useDataTableColumns';
import { Badge } from '@/Components/ui/badge';

type Template = Record<string, any>;

const props = defineProps<{
  templates: {
    data: Template[];
    meta: {
      total: number;
      current_page: number;
      per_page: number;
      last_page: number;
      from: number;
      to: number;
    };
  };
  questionTypes: { value: string; label: string }[];
  filters?: Record<string, unknown>;
  sorting?: { id: string; desc: boolean }[];
}>();

const modelName = 'surveyQuestionTemplates';
const entityName = 'survey_question_template';

const typeLabels = computed(
  () => Object.fromEntries(props.questionTypes.map(type => [type.value, type.label])),
);

const columns = computed(() => [
  createTextColumn<Template>('title', { title: $t('surveys.fields.title'), width: 100 }),
  createTextColumn<Template>('question', { title: $t('surveys.fields.question'), width: 360 }),
  {
    accessorKey: 'type',
    header: () => $t('surveys.fields.type'),
    cell: ({ row }) => typeLabels.value[row.getValue('type') as string] ?? row.getValue('type'),
    size: 140,
  },
  {
    accessorKey: 'tenant',
    header: () => $t('surveys.fields.tenant'),
    // A template without a tenant is shared with everyone; say so rather than showing a blank.
    cell: ({ row }) => row.getValue('tenant')
      ?? h(Badge, { variant: 'outline' }, () => $t('surveys.global_template')),
    enableSorting: false,
    size: 120,
  },
  createStandardActionsColumn<Template>('surveyQuestionTemplates', {
    canView: false,
    canEdit: true,
    canDelete: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<Template>>(() => ({
  modelName,
  entityName,
  data: props.templates.data,
  columns: columns.value,
  getRowId: (row: Template) => `template-${row.id}`,
  totalCount: props.templates.meta.total,
  initialPage: props.templates.meta.current_page,
  pageSize: props.templates.meta.per_page,

  initialFilters: props.filters,
  initialSorting: props.sorting?.length ? props.sorting : [{ id: 'order', desc: false }],
  enableFiltering: true,
  enableColumnVisibility: false,
  enableRowSelection: false,

  headerTitle: $t('surveys.question_bank'),
  icon: ListChecks,
  createRoute: route('surveyQuestionTemplates.create'),
  canCreate: true,
}));
</script>
