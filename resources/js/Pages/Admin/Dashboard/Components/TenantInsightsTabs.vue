<template>
  <!-- One aside, two readings of the padalinys; the tabs stand where a section heading would. -->
  <Tabs v-model="activeTab" class="flex flex-col gap-3" data-slot="tenant-insights-tabs">
    <TabsList
      class="h-auto w-full justify-start gap-6 border-0 border-b border-border bg-transparent p-0 sm:w-full sm:justify-start"
      :aria-label="$t('visak.tenant_overview.insights')"
    >
      <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value" :class="triggerClass">
        <component :is="tab.icon" class="size-4 text-brand" aria-hidden="true" />
        {{ tab.label }}
      </TabsTrigger>
    </TabsList>

    <TabsContent value="trend" class="mt-0">
      <OverviewChart :summary="trendSummary">
        <InstitutionStatusTrendChart
          :data="trendData"
          :days
          :loading="trendLoading"
          @update:days="emit('update:days', $event)"
        />
      </OverviewChart>
    </TabsContent>

    <TabsContent value="representatives" class="mt-0">
      <RepresentativeActivitySection
        v-if="representativeActivity"
        :stats="representativeActivity.stats"
        :users="representativeActivity.preview_users"
        :tenant-ids
        :loading="representativesLoading"
      />
      <Skeleton v-else class="h-40 w-full" />
    </TabsContent>
  </Tabs>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { TrendingDown, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import type { InstitutionStatusHistoryPoint, RepresentativeActivityData } from '../types';

import InstitutionStatusTrendChart from './InstitutionStatusTrendChart.vue';
import RepresentativeActivitySection from './RepresentativeActivitySection.vue';

import OverviewChart from '@/Components/Overview/OverviewChart.vue';
import { Skeleton } from '@/Components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';

defineProps<{
  trendData: InstitutionStatusHistoryPoint[];
  trendSummary: string;
  trendLoading: boolean;
  days: number;
  representativeActivity?: RepresentativeActivityData;
  representativesLoading: boolean;
  tenantIds: string[];
}>();

const emit = defineEmits<{
  'update:days': [days: number];
}>();

const activeTab = ref<'trend' | 'representatives'>('trend');

const tabs = computed(() => [
  { value: 'trend', label: $t('visak.overview.trend.title'), icon: TrendingDown },
  { value: 'representatives', label: $t('visak.tenant_overview.representatives'), icon: Users },
]);

// The home section heading's voice (OverviewSection `home`), with the record page's underline.
const triggerClass = [
  '-mb-px h-auto flex-none gap-2 border-0 border-b-2 border-transparent px-0 pb-3 pt-0',
  'text-sm font-bold uppercase tracking-[0.18em] text-muted-foreground hover:text-foreground',
  'data-[state=active]:border-brand data-[state=active]:bg-transparent data-[state=active]:text-foreground',
  'pointer-coarse:min-h-11',
].join(' ');
</script>
