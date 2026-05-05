<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-[95vw] w-full max-h-[85vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>{{ $t('Visi susitikimai') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Peržiūrėkite visus savo susitikimus') }}
        </DialogDescription>
      </DialogHeader>
      <div class="space-y-4">
        <SimpleDataTable
          :data="sortedMeetings"
          :columns="modernMeetingColumns"
          :enable-pagination="true"
          :page-size="10"
          :enable-filtering="true"
          :enable-column-visibility="false"
          :empty-message="$t('Susitikimų nerasta')"
        />
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="tsx">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';

import type { AtstovavimosMeeting } from '../types';

import SimpleDataTable from '@/Components/Tables/SimpleDataTable.vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { createIdColumn } from '@/Utils/DataTableColumns.tsx';
import { formatStaticTime } from '@/Utils/IntlTime';
import { cn } from '@/Utils/Shadcn/utils';

interface Props {
  meetings: AtstovavimosMeeting[];
  isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:isOpen': [value: boolean];
}>();

// Sort meetings from newest to oldest
const sortedMeetings = computed(() => {
  return props.meetings.sort((a: any, b: any) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime());
});

// Modern TanStack columns for meetings
const modernMeetingColumns = computed<ColumnDef<any, any>[]>(() => [
  createIdColumn({ width: 60 }),
  {
    accessorKey: 'institutions.0.name',
    id: 'institution',
    size: 160,
    enableSorting: true,
    header: (info) => {
      const label = $t('Institucija');
      const truncatedLabel = label.length > 8 ? `${label.substring(0, 8)}...` : label;
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
              <p class="max-w-xs">{label}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
    cell: ({ row }) => {
      const institutionName = row.original.institutions[0]?.name || '';

      if (String(institutionName).length > 35) {
        return (
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger asChild>
                <Link
                  href={route('institutions.show', row.original.institutions[0].id)}
                  class="text-primary hover:underline max-w-[140px] truncate block cursor-help"
                >
                  {institutionName}
                </Link>
              </TooltipTrigger>
              <TooltipContent side="top" align="start">
                <p class="max-w-xs">{institutionName}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        );
      }

      return (
        <Link
          href={route('institutions.show', row.original.institutions[0].id)}
          class="text-primary hover:underline"
        >
          {institutionName}
        </Link>
      );
    },
  },
  {
    accessorKey: 'start_time',
    id: 'start_time',
    size: 160,
    enableSorting: true,
    sortingFn: (rowA, rowB) => {
      const dateA = new Date(rowA.original.start_time).getTime();
      const dateB = new Date(rowB.original.start_time).getTime();
      return dateB - dateA; // Sort newest first
    },
    header: (info) => {
      const label = $t('Susitikimo pradžia');
      const truncatedLabel = 'Pradžia';
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
              <p class="max-w-xs">{label}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
    cell: ({ row }) => {
      const startTime = new Date(row.original.start_time);
      const formattedTime = formatStaticTime(startTime, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
      });

      return (
        <Link
          href={route('meetings.show', row.original.id)}
          class="text-primary hover:underline"
        >
          {formattedTime}
        </Link>
      );
    },
  },
  {
    accessorKey: 'start_time',
    id: 'status',
    size: 100,
    enableSorting: true,
    header: () => {
      const label = $t('Būsena');
      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <span class="cursor-help text-sm font-medium">
                {label}
              </span>
            </TooltipTrigger>
            <TooltipContent>
              <p class="max-w-xs">{$t('Ar susitikimas būsimas ar įvykęs')}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
    cell: ({ row }) => {
      const startTime = new Date(row.original.start_time);
      const isUpcoming = startTime > new Date();

      return (
        <div class="flex items-center gap-2">
          <span class={cn(
            'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
            isUpcoming
              ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400'
              : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800/50 dark:text-zinc-400',
          )}
          >
            {isUpcoming ? $t('Būsimas') : $t('Įvykęs')}
          </span>
        </div>
      );
    },
  },
  {
    accessorKey: 'completion_status',
    id: 'completion',
    size: 120,
    enableSorting: true,
    header: () => {
      const label = $t('Užbaigimas');
      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <span class="cursor-help text-sm font-medium">
                {label}
              </span>
            </TooltipTrigger>
            <TooltipContent>
              <p class="max-w-xs">{$t('Darbotvarkės užpildymo būsena')}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
    cell: ({ row }) => {
      const status = row.original.completion_status;

      if (!status) {
        return <span class="text-xs text-zinc-400 dark:text-zinc-500">—</span>;
      }

      const statusConfig = {
        complete: {
          label: $t('Užbaigtas'),
          color: 'bg-black text-white dark:bg-white dark:text-black',
          icon: '●',
        },
        incomplete: {
          label: $t('Neužbaigtas'),
          color: 'bg-red-600 text-white dark:bg-red-600',
          icon: '●',
        },
        no_items: {
          label: $t('Be darbotvarkės'),
          color: 'border border-red-600 text-red-600 dark:border-red-500 dark:text-red-500',
          icon: '○',
        },
      };

      const config = statusConfig[status];

      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <span class={cn(
                'inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium cursor-help',
                config.color,
              )}
              >
                <span>{config.icon}</span>
                <span class="hidden sm:inline">{config.label}</span>
              </span>
            </TooltipTrigger>
            <TooltipContent>
              <p>{config.label}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
  },
]);
</script>
