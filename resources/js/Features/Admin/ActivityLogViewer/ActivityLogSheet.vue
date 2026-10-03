<template>
  <Sheet v-model:open="open">
    <SheetTrigger v-if="!hideTrigger" as-child>
      <Button size="icon" variant="outline" :title="$t('activity.title')" :aria-label="$t('activity.title')">
        <History class="size-4" />
      </Button>
    </SheetTrigger>
    <SheetContent
      data-slot="activity-log-sheet"
      :side="isMobile ? 'bottom' : 'right'"
      :class="['flex flex-col gap-0 p-0', isMobile ? 'h-[92dvh] max-h-[92dvh]' : 'w-full sm:max-w-xl']"
    >
      <SheetHeader class="border-b border-border px-6 py-4">
        <SheetTitle class="text-xl font-semibold tracking-tight text-foreground">
          {{ $t('activity.title') }}
        </SheetTitle>
      </SheetHeader>

      <div class="flex flex-wrap items-center gap-2 border-b border-border px-6 py-3">
        <Button
          size="sm"
          voice="sentence"
          :variant="scope === 'tree' ? 'secondary' : 'ghost'"
          @click="setScope('tree')"
        >
          {{ $t('activity.filter.scope_tree') }}
        </Button>
        <Button
          size="sm"
          voice="sentence"
          :variant="scope === 'self' ? 'secondary' : 'ghost'"
          @click="setScope('self')"
        >
          {{ $t('activity.filter.scope_self') }}
        </Button>

        <!-- Only once the feed has mixed subject types (useActivityLog's knownSubjectTypes). -->
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

      <ScrollArea class="min-h-0 flex-1 px-6">
        <div class="py-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
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
import { computed, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { History } from 'lucide-vue-next';

import ActivityLogFeed from './ActivityLogFeed.vue';

import { Button } from '@/Components/ui/button';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/Components/ui/sheet';
import { useActivityLog } from '@/Composables/useActivityLog';
import { useIsMobile } from '@/Composables/useIsMobile';

const props = defineProps<{
  subjectType: string;
  subjectId: string;
  /** Opened from elsewhere (a record's ⋯ menu) through `v-model:open`. */
  hideTrigger?: boolean;
}>();

const open = defineModel<boolean>('open', { default: false });

const isMobile = useIsMobile();
const activityLog = useActivityLog(props.subjectType, props.subjectId);

const scope = computed(() => activityLog.filters.value.scope ?? 'tree');
const subjectTypeFilter = computed(() => activityLog.filters.value.subject_type ?? 'all');

// Backend subject.type values are camelCase aliases from App\Support\Auditables
// (e.g. "agendaItem"), so a word-boundary split reads without a label per type.
const availableSubjectTypeOptions = computed(() =>
  activityLog.availableSubjectTypes.value
    .map(type => ({ value: type, label: humanizeSubjectType(type) }))
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

watch(open, (isOpen) => {
  if (!isOpen) return;

  if (!activityLog.hasLoadedOnce.value) {
    void activityLog.load();
  }
});
</script>
