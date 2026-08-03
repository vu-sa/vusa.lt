<template>
  <Sheet @update:open="handleOpenChange">
    <SpotlightPopover
      :title="$t('activity.title')"
      :description="$t('activity.spotlight_description')"
      :is-dismissed="spotlight.isDismissed.value"
      float
      style="display: inline-flex;"
      @dismiss="spotlight.dismiss"
    >
      <SheetTrigger as-child>
        <Button size="icon-sm" variant="ghost" class="rounded-full" :title="$t('activity.title')">
          <History class="h-4 w-4" />
        </Button>
      </SheetTrigger>
    </SpotlightPopover>
    <SheetContent class="flex w-full flex-col sm:max-w-xl">
      <SheetHeader>
        <SheetTitle>{{ $t('activity.title') }}</SheetTitle>
      </SheetHeader>

      <div class="flex flex-wrap items-center gap-2 border-b border-zinc-200 px-4 pb-3 dark:border-zinc-700">
        <Button
          size="sm"
          :variant="scope === 'tree' ? 'secondary' : 'ghost'"
          @click="setScope('tree')"
        >
          {{ $t('activity.filter.scope_tree') }}
        </Button>
        <Button
          size="sm"
          :variant="scope === 'self' ? 'secondary' : 'ghost'"
          @click="setScope('self')"
        >
          {{ $t('activity.filter.scope_self') }}
        </Button>

        <!--
          Only shown once the feed has actually mixed subject types (see
          useActivityLog's knownSubjectTypes) -- a roll-up feed that has only
          ever contained one type (e.g. scope=self, or a root with no logged
          descendants) has nothing to filter.
        -->
        <Select
          v-if="availableSubjectTypeOptions.length > 1"
          :model-value="subjectTypeFilter"
          @update:model-value="(value) => setSubjectType(value as string)"
        >
          <SelectTrigger size="sm" class="ml-auto w-auto min-w-32">
            <SelectValue :placeholder="$t('activity.filter.subject_type')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">
              {{ $t('activity.filter.all_types') }}
            </SelectItem>
            <SelectItem v-for="option in availableSubjectTypeOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <ScrollArea class="min-h-0 flex-1 px-4">
        <div class="py-4">
          <ActivityLogFeed
            :entries="activityLog.entries.value"
            :loading="activityLog.loading.value"
            :loading-more="activityLog.loadingMore.value"
            :has-more="activityLog.hasMore.value"
            @load-more="activityLog.loadMore"
          />
        </div>
      </ScrollArea>
    </SheetContent>
  </Sheet>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { History } from 'lucide-vue-next';

import ActivityLogFeed from './ActivityLogFeed.vue';

import { Button } from '@/Components/ui/button';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/Components/ui/sheet';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useActivityLog } from '@/Composables/useActivityLog';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

const props = defineProps<{
  subjectType: string;
  subjectId: string;
}>();

const activityLog = useActivityLog(props.subjectType, props.subjectId);
const spotlight = useFeatureSpotlight('activity-log-v1');

const scope = computed(() => activityLog.filters.value.scope ?? 'tree');
const subjectTypeFilter = computed(() => activityLog.filters.value.subject_type ?? 'all');

// Backend subject.type values are camelCase aliases from App\Support\Auditables
// (e.g. "agendaItem", "contentPart"), so a plain word-boundary split reads
// naturally without a dedicated label per type.
const availableSubjectTypeOptions = computed(() =>
  activityLog.availableSubjectTypes.value
    .map((type) => ({ value: type, label: humanizeSubjectType(type) }))
    .sort((a, b) => a.label.localeCompare(b.label)),
);

function humanizeSubjectType(type: string): string {
  const spaced = type.replace(/([a-z0-9])([A-Z])/g, '$1 $2');

  return spaced.charAt(0).toUpperCase() + spaced.slice(1);
}

function setScope(next: 'tree' | 'self'): void {
  activityLog.setFilters({ ...activityLog.filters.value, scope: next });
}

function setSubjectType(value: string): void {
  activityLog.setFilters({ ...activityLog.filters.value, subject_type: value === 'all' ? undefined : value });
}

function handleOpenChange(open: boolean): void {
  if (!open) return;

  spotlight.dismiss();

  if (!activityLog.hasLoadedOnce.value) {
    void activityLog.load();
  }
}
</script>
