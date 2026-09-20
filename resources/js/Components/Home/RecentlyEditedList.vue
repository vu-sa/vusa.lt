<template>
  <OverviewSection :title="$t('Neseniai redaguota')" :empty="records.length === 0">
    <ul class="divide-y divide-border border-y border-border" data-slot="recently-edited">
      <li v-for="record in records" :key="`${record.type}:${record.id}`">
        <Link
          :href="record.href"
          prefetch
          class="flex items-center gap-3 px-1 py-3 hover:bg-secondary focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring pointer-coarse:py-4"
        >
          <EntityTypeMark :type="record.type" />
          <span class="min-w-0 flex-1 truncate font-medium">{{ record.title }}</span>
          <span class="shrink-0 text-sm text-muted-foreground">{{ formatNearDate(record.changed_at) }}</span>
        </Link>
      </li>
    </ul>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import type { HomeRecentRecord } from './types';

import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { formatNearDate } from '@/Utils/dateTime';

defineProps<{
  records: HomeRecentRecord[];
}>();
</script>
