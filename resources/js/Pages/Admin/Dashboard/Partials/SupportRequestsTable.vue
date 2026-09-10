<template>
  <DataTable
    :columns
    :data="requests.data"
    :pagination="false"
    manual-sorting
    :external-sorting="sorting"
    :empty-message="$t('Pranešimų nerasta')"
    @update:sorting="$emit('change-sorting', $event)"
  >
    <template #empty>
      <div class="flex flex-col items-center justify-center gap-3 py-10 text-center">
        <p class="text-sm text-muted-foreground">
          {{ $t('Pranešimų nerasta') }}
        </p>
      </div>
    </template>
    <template #pagination>
      <div v-if="requests.last_page > 1" class="flex items-center justify-between gap-4 border-t px-3 py-2">
        <p class="text-xs text-muted-foreground tabular-nums">
          {{ requests.from ?? 0 }}–{{ requests.to ?? 0 }} / {{ requests.total }}
        </p>
        <div class="flex items-center gap-2">
          <Button variant="outline" size="icon-sm" :disabled="requests.current_page === 1" @click="$emit('change-page', requests.current_page - 1)">
            <ChevronLeft class="size-4" />
            <span class="sr-only">{{ $t('Ankstesnis') }}</span>
          </Button>
          <span class="text-xs tabular-nums text-muted-foreground">{{ requests.current_page }} / {{ requests.last_page }}</span>
          <Button variant="outline" size="icon-sm" :disabled="requests.current_page === requests.last_page" @click="$emit('change-page', requests.current_page + 1)">
            <ChevronRight class="size-4" />
            <span class="sr-only">{{ $t('Kitas') }}</span>
          </Button>
        </div>
      </div>
    </template>
  </DataTable>
</template>

<script setup lang="tsx">
import { computed, h } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef, SortingState } from '@tanstack/vue-table';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

import { DataTable } from '@/Components/ui/data-table';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { TruncatedLink } from '@/Components/ui/data-table/cells';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import type { SupportRequestItem } from '@/Types/supportRequests';

const props = defineProps<{
  requests: {
    data: SupportRequestItem[];
    total: number;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
  };
  sorting: SortingState;
  statusOptions: Array<{ value: string; label: string; badgeVariant: string }>;
}>();

defineEmits<{
  'change-page': [page: number];
  'change-sorting': [sorting: SortingState];
}>();

const page = usePage();
const locale = computed(() => (page.props as { app?: { locale?: string } }).app?.locale ?? 'lt');

const statusValue = (status: SupportRequestItem['status']) =>
  typeof status === 'object' ? status.value : status;

const statusVariant = (status: string) => ({
  new: 'sky',
  reviewing: 'warning',
  planned: 'zinc',
  in_progress: 'amber',
  done: 'success',
  declined: 'destructive',
}[status] ?? 'secondary') as 'sky' | 'warning' | 'zinc' | 'amber' | 'success' | 'destructive' | 'secondary';

const statusLabel = (status: SupportRequestItem['status']) => {
  const value = statusValue(status);
  return props.statusOptions.find(option => option.value === value)?.label ?? value;
};

const formatDate = (value: string) => new Intl.DateTimeFormat(locale.value, {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
}).format(new Date(value));

const columns = computed<ColumnDef<SupportRequestItem>[]>(() => [
  {
    accessorKey: 'title',
    header: () => $t('Pavadinimas'),
    size: 310,
    cell: ({ row }) => h('div', { class: 'min-w-0' }, [
      h(TruncatedLink, {
        href: route('supportRequests.show', row.original.id),
        text: row.original.title || '—',
        lines: 2,
      }),
      h('span', { class: 'block truncate text-xs text-muted-foreground' },
        `${getTranslatedValue(row.original.type?.name, locale.value, '—')} · ${getTranslatedValue(row.original.area?.name, locale.value, '—')}`),
    ]),
  },
  {
    id: 'reporter',
    header: () => $t('Pateikė'),
    size: 170,
    cell: ({ row }) => row.original.creator?.name ?? row.original.reporter_name ?? $t('Svečias'),
  },
  {
    accessorKey: 'status',
    header: () => $t('Būsena'),
    size: 140,
    cell: ({ row }) => h(Badge, {
      variant: statusVariant(statusValue(row.original.status)),
    }, () => statusLabel(row.original.status)),
  },
  {
    id: 'assigned_to',
    header: () => $t('Priskirta'),
    size: 170,
    cell: ({ row }) => row.original.assignedTo?.name ?? '—',
  },
  {
    accessorKey: 'created_at',
    header: () => $t('Sukurta'),
    size: 140,
    cell: ({ row }) => h('span', { class: 'text-sm text-muted-foreground' }, formatDate(row.original.created_at)),
  },
]);
</script>
