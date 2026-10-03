<template>
  <!-- Nothing edited is not a status worth reporting, so an empty list leaves no trace. -->
  <OverviewSection v-if="records.length > 0" :title="$t('Neseniai redaguota')" :icon="History" variant="home">
    <ul class="divide-y divide-border/60" data-slot="recently-edited">
      <li v-for="record in records" :key="`${record.type}:${record.id}`">
        <Link
          :href="record.href"
          prefetch
          class="flex flex-col gap-1 px-3 py-4 hover:bg-secondary/40 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
        >
          <span class="line-clamp-2 text-pretty font-bold text-foreground">{{ record.title }}</span>
          <span class="flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground">
            <EntityTypeMark :type="record.type" class="text-xs" />
            <span aria-hidden="true">·</span>
            <span>{{ formatNearDate(record.changed_at, { thresholdDays: 365 }) }}</span>
          </span>
        </Link>
      </li>
    </ul>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { History } from 'lucide-vue-next';

import type { HomeRecentRecord } from './types';

import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { useDateFormatter } from '@/Composables/useDateFormatter';

defineProps<{
  records: HomeRecentRecord[];
}>();

const { formatNearDate } = useDateFormatter();
</script>
