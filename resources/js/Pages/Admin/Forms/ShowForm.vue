<template>
  <RecordPage
    v-model:section="currentSection"
    :title="localizedTitle"
    :entity-type="ModelEnum.FORM"
    :facts="recordFacts"
    :sections="tabs"
    :primary-action
    :overflow-actions
    @action="handleRecordAction"
  >
    <template #subtitle>
      <div v-if="form.tenant?.shortname" class="text-xs text-muted-foreground">
        {{ form.tenant.shortname }}
      </div>
    </template>

    <template #registracijos>
      <div class="space-y-4">
        <SimpleDataTable
          :data="tableData"
          :columns="registrationColumns"
          enable-pagination
          :page-size="15"
          enable-filtering
          :enable-column-visibility="false"
          :empty-message="$t('forms.registrations.none')"
          :row-class-name="() => ''"
        >
          <template #empty>
            <EmptyState
              :icon="Inbox"
              :title="$t('forms.registrations.none')"
              :description="$t('forms.registrations.none_hint')"
              :action-label="publicUrl ? $t('Atidaryti viešą formą') : undefined"
              @action="publicUrl && openPublicForm()"
            />
          </template>

          <template #filters>
            <DataTableFilter
              v-for="field in enumFields"
              :key="field.id"
              v-model:value="enumFilters[String(field.id)]"
              :options="getFieldOptions({ key: String(field.id) })"
              multiple
              @update:value="enumFilters[String(field.id)] = $event"
            >
              {{ field.label }}
            </DataTableFilter>
          </template>
        </SimpleDataTable>
      </div>
    </template>

    <template #laukai>
      <div class="max-w-3xl space-y-3">
        <div
          v-for="(field, index) in form.form_fields"
          :key="field.id"
          class="border border-border bg-card p-4"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono text-muted-foreground">{{ index + 1 }}.</span>
                <h4 class="text-sm font-semibold text-foreground">
                  {{ field.label }}
                </h4>
                <Badge v-if="field.is_required" variant="destructive" class="text-[10px]">
                  {{ $t('Privalomas') }}
                </Badge>
              </div>
              <p v-if="field.description" class="text-xs text-muted-foreground">
                {{ field.description }}
              </p>
            </div>
            <Badge variant="outline" class="shrink-0 text-xs">
              {{ fieldTypeLabel(field.type) }}
            </Badge>
          </div>

          <div v-if="field.options && field.options.length > 0" class="mt-3 border-t border-border pt-3">
            <span class="text-xs font-medium text-muted-foreground">{{ $t('Galimos reikšmės') }}:</span>
            <div class="mt-1.5 flex flex-wrap gap-1.5">
              <span
                v-for="opt in field.options"
                :key="opt.value"
                class="border border-border bg-secondary px-2 py-0.5 text-xs text-foreground"
              >
                {{ typeof opt.label === 'object' ? opt.label[$page.props.app.locale] : opt.label }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template #veikla>
      <RecordActivity
        subject-type="form"
        :subject-id="form.id"
        commentable-type="form"
        :commentable-id="form.id"
      />
    </template>
  </RecordPage>

  <!-- Registration Details Dialog -->
  <Dialog v-model:open="showModal">
    <DialogContent class="max-h-[85vh] w-full max-w-4xl overflow-y-auto">
      <DialogHeader>
        <DialogTitle>{{ $t('forms.registrations.details') }}</DialogTitle>
        <DialogDescription>
          {{ $t('forms.registrations.details_hint') }}
        </DialogDescription>
      </DialogHeader>

      <div v-if="selectedRegistration" class="space-y-4 pt-2">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div
            v-for="field in displayFields"
            :key="field.key"
            class="border border-border bg-secondary/40 p-3"
          >
            <div class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
              {{ field.title }}
            </div>
            <div class="mt-1 text-sm font-medium text-foreground break-words">
              {{ formatFieldValue(field, selectedRegistration[field.key]) }}
            </div>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>

  <ConfirmDialog
    v-model:open="showDeleteDialog"
    :title="$t('Šalinti formą?')"
    :description="$t('Ar tikrai norite ištrinti šią formą? Forma bus perkelta į šiukšlinę.')"
    :confirm-label="$t('Šalinti')"
    destructive
    @confirm="handleDelete"
  />
</template>

<script setup lang="tsx">
import { router, usePage } from '@inertiajs/vue3';
import type { CellContext, ColumnDef, HeaderContext, TableFeatures } from '@tanstack/vue-table';
import { getActiveLanguage, trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Copy, Download, Edit, ExternalLink, Eye, Inbox, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import RecordPage, { type RecordAction, type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import SimpleDataTable from '@/Components/Tables/SimpleDataTable.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import DataTableFilter from '@/Components/ui/data-table/DataTableFilter.vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { FormIcon } from '@/Components/icons';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { createIdColumn, createTimestampColumn } from '@/Composables/useDataTableColumns';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import { ModelEnum } from '@/Types/enums';

interface FieldOption {
  value: string | number;
  label: string;
}

const props = defineProps<{
  form: App.Entities.Form;
  registrations: App.Entities.Registration[];
  institutions?: { id: string; name: string }[];
  exportUrl: string | null;
  publicUrl: string | null;
  can: {
    update: boolean;
    export: boolean;
    delete?: boolean;
  };
}>();

const currentSection = ref('registracijos');
const showModal = ref(false);
const showDeleteDialog = ref(false);
const selectedRegistration = ref<Record<string, unknown> | null>(null);

const enumFilters = ref<Record<string, unknown[]>>({});

const localizedTitle = computed(() => {
  if (!props.form.name) return '';
  if (typeof props.form.name === 'object') {
    const locale = getActiveLanguage() as 'lt' | 'en';
    return getTranslatedValue(props.form.name as unknown as Record<string, string>, locale);
  }
  return String(props.form.name);
});

const latestRegistrationDate = computed(() => {
  const latest = props.registrations[0]?.created_at;
  return latest ? new Date(latest).toLocaleString() : null;
});

const recordFacts = computed<RecordFact[]>(() => [
  { key: 'registrations', label: $t('Registracijos'), value: String(props.registrations.length) },
  { key: 'latest', label: $t('Paskutinė registracija'), value: latestRegistrationDate.value ?? '—' },
  ...(props.form.tenant ? [{ key: 'tenant', label: $tChoice('entities.tenant.model', 1), value: props.form.tenant.shortname }] : []),
  ...(props.publicUrl ? [{ key: 'public', label: $t('Nuoroda'), value: $t('Vieša forma'), href: props.publicUrl }] : []),
]);

const tabs = computed<RecordPageSection[]>(() => [
  { value: 'registracijos', label: $t('Registracijos'), count: props.registrations.length },
  { value: 'laukai', label: $t('Formos laukai'), count: props.form.form_fields?.length ?? 0 },
]);

const primaryAction = computed<RecordAction | undefined>(() => {
  if (props.can.export && props.exportUrl) {
    return {
      key: 'export',
      label: $t('Atsisiųsti Excel'),
      icon: Download,
      href: props.exportUrl,
      external: true,
    };
  }
  return undefined;
});

const overflowActions = computed<RecordAction[]>(() => {
  const actions: RecordAction[] = [];

  if (props.can.update) {
    actions.push({
      key: 'edit',
      label: $t('Redaguoti formą'),
      icon: Edit,
      href: route('forms.edit', props.form.id),
    });
  }

  if (props.publicUrl) {
    actions.push({
      key: 'view-public',
      label: $t('Atidaryti viešą formą'),
      icon: ExternalLink,
      href: props.publicUrl,
      external: true,
    });
    actions.push({
      key: 'copy-link',
      label: $t('Kopijuoti nuorodą'),
      icon: Copy,
    });
  }

  actions.push({
    key: 'delete',
    label: $t('Šalinti formą'),
    icon: Trash2,
    destructive: true,
  });

  return actions;
});

function handleRecordAction(key: string): void {
  if (key === 'copy-link') {
    copyPublicLink();
  }
  else if (key === 'delete') {
    showDeleteDialog.value = true;
  }
}

function copyPublicLink(): void {
  if (!props.publicUrl) return;
  navigator.clipboard.writeText(props.publicUrl);
  toast.success($t('Nuoroda nukopijuota į iškarpinę.'));
}

function openPublicForm(): void {
  if (!props.publicUrl) return;
  window.open(props.publicUrl, '_blank');
}

function fieldTypeLabel(type: string): string {
  const map: Record<string, string> = {
    string: $t('Tekstas'),
    boolean: $t('Taip / Ne'),
    enum: $t('Pasirinkimas'),
    number: $t('Skaičius'),
    date: $t('Data'),
  };
  return map[type] ?? type;
}

const getFieldOptions = (field: { key: string }): FieldOption[] | null => {
  const formField = props.form.form_fields.find(f => String(f.id) === field.key);
  if (!formField || formField.type !== 'enum') return null;

  if (formField.use_model_options && formField.options_model === 'App\\Models\\Tenant') {
    const tenants = usePage().props.tenants || [];
    return tenants.map((tenant: { id: number; shortname: string }) => ({
      value: tenant.id,
      label: tenant.shortname,
    }));
  }

  if (formField.use_model_options && formField.options_model === 'App\\Models\\Institution') {
    const institutions = props.institutions || [];
    return institutions.map(institution => ({
      value: institution.id,
      label: institution.name,
    }));
  }

  if (formField.options && Array.isArray(formField.options)) {
    return formField.options.map((option: { value: string | number; label: string | Record<string, string> }) => ({
      value: option.value,
      label: typeof option.label === 'object' ? option.label[usePage().props.app.locale] : String(option.label),
    }));
  }

  return null;
};

const enumFields = computed(() => {
  return props.form.form_fields.filter((field) => {
    if (field.type !== 'enum') return false;
    const options = getFieldOptions({ key: String(field.id) });
    return options && Array.isArray(options) && options.length > 0;
  });
});

watch(enumFields, (fields) => {
  fields.forEach((field) => {
    if (!enumFilters.value[String(field.id)]) {
      enumFilters.value[String(field.id)] = [];
    }
  });
}, { immediate: true });

const tableData = computed(() => {
  let filteredRegistrations = props.registrations;

  Object.entries(enumFilters.value).forEach(([fieldId, selectedValues]) => {
    if (selectedValues.length > 0) {
      filteredRegistrations = filteredRegistrations.filter((registration) => {
        const fieldResponse = registration.field_responses.find(
          fr => String(fr.form_field.id) === fieldId,
        );
        const value = fieldResponse?.response?.value;
        return selectedValues.includes(value);
      });
    }
  });

  return filteredRegistrations.map((registration) => {
    const row: Record<string, unknown> = { ...registration };
    registration.field_responses.forEach((fieldResponse) => {
      if (fieldResponse.response && fieldResponse.response.value !== undefined) {
        row[String(fieldResponse.form_field.id)] = fieldResponse.response.value;
      }
      else {
        row[String(fieldResponse.form_field.id)] = '';
      }
    });
    return row;
  });
});

const formatFieldValue = (field: { key?: string; id?: string | number; type?: string }, value: unknown): string => {
  if (value === null || value === undefined || value === '') {
    return '—';
  }

  if (field.type === 'boolean') {
    return value ? $t('Taip') : $t('Ne');
  }

  if (field.type === 'enum') {
    const formField = field.id
      ? props.form.form_fields.find(f => String(f.id) === String(field.id))
      : props.form.form_fields.find(f => String(f.id) === field.key);

    if (!formField) return String(value);

    if (formField.use_model_options && formField.options_model === 'App\\Models\\Tenant') {
      const tenants = usePage().props.tenants || [];
      const tenant = tenants.find((t: { id: number; shortname: string }) => String(t.id) === String(value) || t.id === value);
      return tenant ? `${tenant.shortname} (ID: ${String(value)})` : `ID: ${String(value)}`;
    }

    if (formField.use_model_options && formField.options_model === 'App\\Models\\Institution') {
      const institutions = props.institutions || [];
      const institution = institutions.find(i => String(i.id) === String(value) || i.id === value);
      return institution ? institution.name : `ID: ${String(value)}`;
    }

    if (formField.options && Array.isArray(formField.options)) {
      const option = formField.options.find((opt: { value: string | number; label?: string | Record<string, string> }) => String(opt.value) === String(value));
      if (option) {
        const label = typeof option.label === 'object' ? option.label[usePage().props.app.locale] : String(option.label ?? '');
        return `${label} (${String(value)})`;
      }
    }

    return String(value);
  }

  if (field.type === 'timestamp' || field.key === 'created_at') {
    try {
      return new Date(value as string).toLocaleString();
    }
    catch {
      return String(value);
    }
  }

  return String(value);
};

const registrationColumns = computed<ColumnDef<TableFeatures, Record<string, unknown>, unknown>[]>(() => {
  const columns: ColumnDef<TableFeatures, Record<string, unknown>, unknown>[] = [
    {
      id: 'actions',
      header: () => '',
      cell: ({ row }: CellContext<TableFeatures, Record<string, unknown>, unknown>) => (
        <div class="flex justify-center">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => {
              selectedRegistration.value = row.original;
              showModal.value = true;
            }}
          >
            <Eye class="h-4 w-4" />
          </Button>
        </div>
      ),
      size: 50,
      enableSorting: false,
    },
    createTimestampColumn('created_at', {
      title: $t('Sukūrimo laikas'),
      width: 160,
    }),
    createIdColumn({ width: 60 }),
    ...props.form.form_fields.map((field) => {
      const columnWidth = field.type === 'enum' ? 180 : 130;

      return {
        accessorKey: String(field.id),
        id: String(field.id),
        size: columnWidth,
        enableSorting: true,
        header: (info: HeaderContext<TableFeatures, Record<string, unknown>, unknown>) => {
          return (
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger asChild>
                  <button
                    class="cursor-help text-left inline-flex w-full items-center gap-1 text-sm font-medium hover:bg-muted/50 px-1 py-1 transition-colors"
                    onClick={() => info.column.toggleSorting()}
                  >
                    <span class="truncate">{field.label}</span>
                    {info.column.getIsSorted() === 'asc' && <span class="shrink-0"> ↑</span>}
                    {info.column.getIsSorted() === 'desc' && <span class="shrink-0"> ↓</span>}
                  </button>
                </TooltipTrigger>
                <TooltipContent side="top" align="start">
                  <p class="max-w-xs">{field.label}</p>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          );
        },
        cell: ({ row }: CellContext<TableFeatures, Record<string, unknown>, unknown>) => {
          const value = row.getValue(String(field.id));
          const formattedValue = formatFieldValue(field, value);

          if (String(formattedValue).length > 20) {
            return (
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <div class="max-w-[150px] truncate cursor-help">
                      {formattedValue}
                    </div>
                  </TooltipTrigger>
                  <TooltipContent side="top" align="start">
                    <p class="max-w-xs">{formattedValue}</p>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
            );
          }

          return formattedValue;
        },
      };
    }),
  ];

  return columns;
});

const displayFields = computed(() => {
  return [
    { key: 'id', title: 'ID', type: 'text' },
    ...props.form.form_fields.map(field => ({
      key: String(field.id),
      title: field.label,
      type: field.type,
    })),
    { key: 'created_at', title: $t('Sukūrimo laikas'), type: 'timestamp' },
  ];
});

const handleDelete = () => {
  router.delete(route('forms.destroy', props.form.id), {
    onSuccess: () => {
      showDeleteDialog.value = false;
    },
  });
};

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminShow(
    $tChoice('entities.form.model', 2),
    'forms.index',
    {},
    localizedTitle.value,
    FormIcon,
    FormIcon,
  ),
);
</script>
