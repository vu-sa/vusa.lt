<template>
  <Card>
    <CardContent class="flex flex-wrap items-center gap-x-8 gap-y-3 p-4">
      <div class="inline-flex items-center gap-2 text-sm font-medium">
        <Eye class="size-4 text-zinc-500 dark:text-zinc-400" />
        {{ $t('analytics.title') }}

        <TooltipProvider v-if="isPartial">
          <Tooltip>
            <TooltipTrigger as-child>
              <Info class="size-4 cursor-help text-zinc-400 dark:text-zinc-500" />
            </TooltipTrigger>
            <TooltipContent class="max-w-xs">
              {{ $t('analytics.partial_tooltip', { date: dataSinceLabel }) }}
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </div>

      <div v-if="isFetching" class="flex items-center gap-6">
        <Skeleton class="h-8 w-16" />
        <Skeleton class="h-8 w-16" />
      </div>

      <p v-else-if="!data?.available || !data.totals" class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ $t('analytics.unavailable_title') }}
      </p>

      <div v-else class="flex items-center gap-8">
        <div>
          <span class="block text-2xl font-bold leading-tight">{{ data.totals.pageviews }}</span>
          <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('analytics.pageviews') }}</span>
        </div>
        <div>
          <span class="block text-2xl font-bold leading-tight">{{ data.totals.visitors }}</span>
          <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('analytics.visitors') }}</span>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Eye, Info } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

import { Card, CardContent } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { useApi } from '@/Composables/useApi';
import type { ContentAnalyticsData } from '@/Types/api.d';

const props = defineProps<{
  type: 'news' | 'page';
  id: number | string;
  /** publish_time, falling back to created_at. Used only to decide whether to warn that
   *  the figures predate tracking. */
  contentDate?: string | null;
}>();

const { data, isFetching } = useApi<ContentAnalyticsData>(
  route('api.v1.admin.analytics.content', { type: props.type, id: props.id }),
  // The card states its own unavailable case; a toast on every edit page when Umami is
  // down would be noise.
  { showErrorToast: false },
);

const dataSinceLabel = computed(() => data.value?.dataSince ?? '');

/**
 * Content created before tracking began has views we never recorded, so the total is a
 * floor rather than a lifetime figure. Only then is the warning worth the visual noise.
 */
const isPartial = computed(() => {
  if (!props.contentDate || !data.value?.dataSince) {
    return false;
  }

  return new Date(props.contentDate) < new Date(data.value.dataSince);
});
</script>
