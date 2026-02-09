<template>
  <ShowPageLayout :model="form" :title="form.name">
    <template #more-options>
      <MoreOptionsButton edit @edit-click="router.visit(route('forms.edit', form.id))" />
    </template>
    <!-- <a target="_blank" :href="route('forms.export', form.id)" class="mb-4 w-fit block"> -->
    <!--   <Button variant="outline" size="sm"> -->
    <!--     Atsisiųsti Excel -->
    <!--   </Button> -->
    <!-- </a> -->

    <div class="space-y-4">
      <SimpleDataTable
        :data="tableData"
        :columns="registrationColumns"
        :enable-pagination="true"
        :page-size="15"
        :enable-filtering="true"
        :enable-column-visibility="false"
        :empty-message="$t('No registrations found')"
        :row-class-name="() => ''"
      >
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

    <!-- Registration Details Dialog -->
    <Dialog v-model:open="showModal">
      <DialogContent class="sm:max-w-[95vw] w-full max-h-[85vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{{ $t('Registration Details') }}</DialogTitle>
          <DialogDescription>
            {{ $t('View detailed registration information') }}
          </DialogDescription>
        </DialogHeader>

        <div v-if="selectedRegistration" class="space-y-4">
          <!-- Registration Info - Two column layout to use full dialog width -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div v-for="field in displayFields" :key="field.key" class="border rounded-lg p-4 bg-muted/10">
              <!-- Field header with value in a flex layout -->
              <div class="flex flex-col gap-3">
                <div class="flex-shrink-0">
                  <div class="text-sm font-medium text-muted-foreground mb-1">
                    {{ field.title }}
                  </div>
                  <!-- Show field options button for enum fields -->
                  <Button
                    v-if="getFieldOptions(field)"
                    variant="outline"
                    size="sm"
                    class="h-8 px-3 text-xs border-dashed hover:border-solid transition-all"
                    @click="toggleFieldOptions(field.key)"
                  >
                    <span class="mr-1" v-html="showOptionsFor === field.key ? '&#9660;' : '&#9654;'" />
                    {{ showOptionsFor === field.key ? 'Hide options' : 'View options' }}
                  </Button>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-base p-3 bg-background border rounded-md min-h-[44px] flex items-center break-words">
                    {{ formatFieldValue(field, selectedRegistration[field.key]) }}
                  </div>
                </div>
              </div>

              <!-- Collapsible field options for enum fields -->
              <div v-if="getFieldOptions(field) && showOptionsFor === field.key" class="mt-3 pt-3 border-t">
                <div class="text-xs font-medium text-muted-foreground mb-2">
                  {{ $t('Available options') }}:
                </div>
                <div class="flex flex-wrap gap-1">
                  <Badge
                    v-for="option in getFieldOptions(field)"
                    :key="option.value"
                    :variant="String(option.value) === String(selectedRegistration[field.key]) ? 'default' : 'outline'"
                    class="text-xs"
                  >
                    {{ option.label }}
                  </Badge>
                </div>
              </div>
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { EyeIcon } from 'lucide-vue-next';

import ShowPageLayout from '@/Components/Layouts/ShowModel/ShowPageLayout.vue';
import MoreOptionsButton from '@/Components/Buttons/MoreOptionsButton.vue';
import SimpleDataTable from '@/Components/Tables/SimpleDataTable.vue';
import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { Badge } from '@/Components/ui/badge';
import DataTableFilter from '@/Components/ui/data-table/DataTableFilter.vue';
import { createIdColumn, createTimestampColumn, createTextColumn } from '@/Utils/DataTableColumns.tsx';

const props = defineProps<{
  form: App.Entities.Form;
  registrations: App.Entities.Registration[];
  institutions?: { id: string; name: string }[];
}>();

const showModal = ref(false);
const selectedRegistration = ref<any>(null);
const showOptionsFor = ref<string | null>(null);

// Filter state for enum fields
const enumFilters = ref<Record<string, any[]>>({});

const toggleFieldOptions = (fieldKey: string) => {
  showOptionsFor.value = showOptionsFor.value === fieldKey ? null : fieldKey;
};

// Helper function to get field options for enum fields - moved up for computed usage
const getFieldOptions = (field: any) => {
  const formField = props.form.form_fields.find(f => String(f.id) === field.key);

  if (!formField || formField.type !== 'enum') {
    return null;
  }

  // Handle tenant model options
  if (formField.use_model_options && formField.options_model === 'App\\Models\\Tenant') {
    const tenants = usePage().props.tenants || [];
    return tenants.map((tenant: any) => ({
      value: tenant.id,
      label: tenant.shortname,
    }));
  }

  // Handle institution model options
  if (formField.use_model_options && formField.options_model === 'App\\Models\\Institution') {
    const institutions = props.institutions || [];
    return institutions.map((institution: any) => ({
      value: institution.id,
      label: institution.name,
    }));
  }

  // Handle regular options
  if (formField.options && Array.isArray(formField.options)) {
    return formField.options.map((option: any) => ({
      value: option.value,
      label: typeof option.label === 'object' ? option.label[usePage().props.app.locale] : option.label,
    }));
  }

  return null;
};

// Reset options state when dialog closes
watch(showModal, (isOpen) => {
  if (!isOpen) {
    showOptionsFor.value = null;
  }
});

// Get enum fields that can have filters
const enumFields = computed(() => {
  return props.form.form_fields.filter((field) => {
    if (field.type !== 'enum') return false;
    const options = getFieldOptions({ key: String(field.id) });
    return options && Array.isArray(options) && options.length > 0;
  });
});

// Initialize filters for enum fields
watch(enumFields, (fields) => {
  fields.forEach((field) => {
    if (!enumFilters.value[String(field.id)]) {
      enumFilters.value[String(field.id)] = [];
    }
  });
}, { immediate: true });

// Transform registration data for table
const tableData = computed(() => {
  let filteredRegistrations = props.registrations;

  // Apply enum filters
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
    const row = { ...registration };
    registration.field_responses.forEach((fieldResponse) => {
      // Check if response exists and has a value property
      if (fieldResponse.response && fieldResponse.response.value !== undefined) {
        row[fieldResponse.form_field.id] = fieldResponse.response.value;
      }
      else {
        // Set empty string as fallback for missing responses
        row[fieldResponse.form_field.id] = '';
      }
    });
    return row;
  });
});

// Helper function to format field values for display with labels
const formatFieldValue = (field: any, value: any) => {
  if (value === null || value === undefined || value === '') {
    return '-';
  }

  if (field.type === 'boolean') {
    return value ? $t('Yes') : $t('No');
  }

  if (field.type === 'enum') {
    // Handle both table context (field = original form field) and dialog context (field = simplified)
    const formField = field.id
      ? field // Already the full form field (from table)
      : props.form.form_fields.find(f => String(f.id) === field.key); // Lookup needed (from dialog)

    if (!formField) return String(value);

    // Handle tenant model options
    if (formField.use_model_options && formField.options_model === 'App\\Models\\Tenant') {
      const tenants = usePage().props.tenants || [];
      // Handle both string and number value types for tenant ID comparison
      const tenant = tenants.find((tenant: any) => String(tenant.id) === String(value) || tenant.id === value);
      return tenant ? `${tenant.shortname} (ID: ${value})` : `ID: ${value}`;
    }

    // Handle institution model options
    if (formField.use_model_options && formField.options_model === 'App\\Models\\Institution') {
      const institutions = props.institutions || [];
      const institution = institutions.find((inst: any) => String(inst.id) === String(value) || inst.id === value);
      return institution ? institution.name : `ID: ${value}`;
    }

    // Handle regular options - show both label and value
    if (formField.options && Array.isArray(formField.options)) {
      const option = formField.options.find((opt: any) => String(opt.value) === String(value));
      if (option) {
        const label = typeof option.label === 'object' ? option.label[usePage().props.app.locale] : option.label;
        return `${label} (${value})`;
      }
    }

    return String(value);
  }

  if (field.type === 'timestamp' || field.key === 'created_at') {
    try {
      return new Date(value).toLocaleString();
    }
    catch (e) {
      return String(value);
    }
  }

  return String(value);
};

// Create modern TanStack columns
const registrationColumns = computed<ColumnDef<any, any>[]>(() => {
  const columns = [
    // Actions column - moved to beginning
    {
      id: 'actions',
      header: () => '',
      cell: ({ row }) => (
        <div class="flex justify-center">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => {
              selectedRegistration.value = row.original;
              showModal.value = true;
            }}
          >
            <EyeIcon class="h-4 w-4" />
          </Button>
        </div>
      ),
      size: 60,
      enableSorting: false,
    },

    // Created at column - moved to beginning
    createTimestampColumn('created_at', {
      title: $t('Created'),
      width: 160,
    }),

    // ID column
    createIdColumn({ width: 60 }),

    // Dynamic form field columns with smarter sizing
    ...props.form.form_fields.map((field) => {
      // Give enum fields more space for formatted labels
      const columnWidth = field.type === 'enum' ? 180 : 120;

      return {
        accessorKey: String(field.id),
        id: String(field.id),
        size: columnWidth,
        enableSorting: true,
        // Proper TanStack header function - receives header context
        header: (info) => {
          const truncatedLabel = field.label.length > 8 ? `${field.label.substring(0, 8)}...` : field.label;
          return (
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger asChild>
                  <button
                    class="cursor-help text-left inline-block w-full text-sm font-medium hover:bg-muted/50 px-1 py-1 rounded transition-colors"
                    onClick={() => info.column.toggleSorting()}
                  >
                    <span>{truncatedLabel}</span>
                    {info.column.getIsSorted() === 'asc' && <span> ↑</span>}
                    {info.column.getIsSorted() === 'desc' && <span> ↓</span>}
                  </button>
                </TooltipTrigger>
                <TooltipContent side="top" align="start">
                  <p class="max-w-xs">{field.label}</p>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          );
        },
        cell: ({ row }) => {
          const value = row.getValue(String(field.id));
          const formattedValue = formatFieldValue(field, value);

          // Use tooltip for long values
          if (String(formattedValue).length > 20) {
            return (
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <div class="max-w-[140px] truncate cursor-help">
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

// Display fields for dialog - exclude actions and reorder for better UX
const displayFields = computed(() => {
  const fields = [
    { key: 'id', title: 'ID', type: 'text' },
    ...props.form.form_fields.map(field => ({
      key: String(field.id),
      title: field.label,
      type: field.type,
    })),
    { key: 'created_at', title: $t('Created'), type: 'timestamp' },
  ];

  return fields;
});
</script>
