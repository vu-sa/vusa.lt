<template>
  <!-- Nothing edited is not a status worth reporting, so an empty list leaves no trace. -->
  <OverviewSection v-if="records.length > 0" :title="$t('Neseniai redaguota')" :icon="History" variant="home">
    <CollectionRowList :items="records" frame="attached" :item-key="record => `${record.type}:${record.id}`" data-slot="recently-edited">
      <template #default="{ item: record }">
        <Link :href="record.href" prefetch :class="cn(ROW_LINK_CLASS, 'flex-col gap-1')">
          <span class="line-clamp-2 text-pretty font-semibold text-foreground">{{ record.title }}</span>
          <span class="flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground">
            <EntityTypeMark :type="record.type" class="text-xs" />
            <span aria-hidden="true">·</span>
            <span>{{ formatNearDate(record.changed_at, { thresholdDays: 365 }) }}</span>
          </span>
        </Link>
      </template>
    </CollectionRowList>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { History } from '@lucide/vue';

import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import { ROW_LINK_CLASS } from '@/Components/Collection/rowList';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import EntityTypeMark from '@/Components/Patterns/EntityTypeMark.vue';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { cn } from '@/Utils/Shadcn/utils';

import type { HomeRecentRecord } from './types';

defineProps<{
  records: HomeRecentRecord[];
}>();

const { formatNearDate } = useDateFormatter();
</script>
