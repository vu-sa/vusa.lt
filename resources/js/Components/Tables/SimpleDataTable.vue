<template>
  <!--
    SimpleDataTable.vue - Lightweight client-side table
    Use this for small datasets that can be fully loaded in the browser
  -->
  <div class="space-y-4">
    <!-- Search and basic filters -->
    <div v-if="enableFiltering" class="flex gap-2">
      <Input
        v-model="searchText"
        data-admin-collection-search
        :placeholder="$t('tables.search_placeholder')"
        :aria-label="$t('tables.search_placeholder')"
        class="max-w-sm"
      />
      <slot name="filters" />
    </div>

    <!-- Table -->
    <DataTable
      :columns
      :data
      :page-size
      :pagination="enablePagination"
      :row-class-name
      :empty-message
      :enable-filtering="false"
      :global-filter="searchText"
    >
      <template #empty>
        <slot name="empty">
          <div class="flex flex-col items-center justify-center py-8 text-center">
            <Circle class="h-12 w-12 text-muted-foreground mb-4" />
            <h3 class="text-lg font-medium mb-2">
              {{ emptyMessage || $t('tables.no_results') }}
            </h3>
            <p class="text-sm text-muted-foreground">
              {{ $t('tables.no_results_description') }}
            </p>
          </div>
        </slot>
      </template>
    </DataTable>
  </div>
</template>

<script setup lang="ts" generic="TData extends RowData">
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { RowData } from '@tanstack/vue-table';
import { Circle } from '@lucide/vue';

import { Input } from '@/Components/ui/input';
import type { SimpleTableProps } from '@/Types/TableConfigTypes';

import DataTable from '../ui/data-table/DataTable.vue';

withDefaults(defineProps<SimpleTableProps<TData>>(), {
  enablePagination: true,
  pageSize: 10,
  enableFiltering: true,
});

const searchText = ref('');
</script>
