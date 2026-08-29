<template>
  <Sheet :open="open" @update:open="emit('update:open', $event)">
    <SheetContent class="flex w-full flex-col gap-0 sm:max-w-xl">
      <SheetHeader>
        <SheetTitle>{{ $t('dutiables.timeline.diff.title') }}</SheetTitle>
        <SheetDescription>{{ $t('dutiables.timeline.diff.description') }}</SheetDescription>
      </SheetHeader>

      <div v-if="loading" class="space-y-2 p-4">
        <Skeleton v-for="n in 5" :key="n" class="h-10 w-full" />
      </div>

      <template v-else-if="plan">
        <div class="flex flex-wrap gap-1 border-b border-border px-4 pb-3">
          <Badge variant="secondary" class="text-[10px]">
            {{ $t('dutiables.timeline.diff.changed', { count: plan.summary.changed }) }}
          </Badge>
          <Badge v-if="plan.summary.blocked > 0" variant="destructive" class="text-[10px]">
            {{ $t('dutiables.timeline.diff.blocked', { count: plan.summary.blocked }) }}
          </Badge>
          <Badge variant="outline" class="text-[10px]">
            {{ $t('dutiables.timeline.diff.unchanged', { count: plan.summary.unchanged }) }}
          </Badge>
          <Badge v-if="plan.summary.derived > 0" variant="outline" class="text-[10px]">
            {{ $t('dutiables.timeline.diff.derived', { count: plan.summary.derived }) }}
          </Badge>
        </div>

        <!-- The headline number: whether the batch actually removed the drift it was for. -->
        <p v-if="hasDiagnosticsDelta" class="border-b border-border px-4 py-2 text-xs font-medium">
          {{ $t('dutiables.timeline.diff.diagnostics_delta', {
            before: plan.diagnostics_before.length,
            after: plan.diagnostics_after.length,
          }) }}
        </p>

        <Alert v-if="plan.self_affecting" variant="destructive" class="mx-4 mt-3">
          <TriangleAlert class="size-4" />
          <AlertDescription class="text-xs">
            {{ $t('dutiables.timeline.diff.self_affecting') }}
          </AlertDescription>
        </Alert>

        <ScrollArea class="min-h-0 flex-1 px-4">
          <p v-if="plan.changes.length === 0" class="py-8 text-center text-sm text-muted-foreground">
            {{ $t('dutiables.timeline.diff.no_changes') }}
          </p>

          <ul v-else class="divide-y divide-border">
            <li v-for="change in plan.changes" :key="change.row_id" class="py-3">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium">
                    {{ change.holder_name ?? '—' }}
                  </p>
                  <p class="truncate text-xs text-muted-foreground">
                    {{ change.duty_name ?? '—' }}
                  </p>
                </div>
                <Badge v-if="change.blocked" variant="destructive" class="shrink-0 text-[10px]">
                  {{ $t(`dutiables.timeline.blocked.${change.blocked}`) }}
                </Badge>
              </div>

              <div class="mt-1 flex flex-wrap items-center gap-2 font-mono text-[11px]">
                <span class="text-muted-foreground">{{ formatPeriod(change.before) }}</span>
                <ArrowRight class="size-3 text-muted-foreground" />
                <span :class="change.blocked ? 'text-muted-foreground line-through' : 'text-amber-600 dark:text-amber-400'">
                  {{ formatPeriod(change.after) }}
                </span>
              </div>

              <ul v-if="change.derived.length > 0" class="mt-1 space-y-0.5 pl-4">
                <li v-for="derived in change.derived" :key="derived.id" class="text-[11px] text-muted-foreground">
                  ↳ {{ derived.duty_name ?? '—' }} · {{ formatPeriod(derived) }}
                </li>
              </ul>
            </li>
          </ul>
        </ScrollArea>
      </template>

      <SheetFooter class="border-t border-border pt-3">
        <Button variant="ghost" size="sm" @click="emit('update:open', false)">
          {{ $t('dutiables.timeline.diff.cancel') }}
        </Button>
        <Button size="sm" :disabled="processing || !canConfirm" @click="emit('confirm')">
          {{ $t('dutiables.timeline.diff.confirm') }}
        </Button>
      </SheetFooter>
    </SheetContent>
  </Sheet>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { ArrowRight, TriangleAlert } from 'lucide-vue-next';

import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { Skeleton } from '@/Components/ui/skeleton';

import type { TimelinePlanPayload } from './types';

const props = defineProps<{
  open: boolean;
  plan: TimelinePlanPayload | null;
  loading: boolean;
  processing: boolean;
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
  confirm: [];
}>();

const canConfirm = computed(() => (props.plan?.summary.changed ?? 0) > 0);

/** Only worth showing once the diagnostics producer exists; otherwise both sides are 0. */
const hasDiagnosticsDelta = computed(
  () => (props.plan?.diagnostics_before.length ?? 0) + (props.plan?.diagnostics_after.length ?? 0) > 0,
);

function formatPeriod(period: { start_date: string; end_date: string | null }): string {
  return `${period.start_date} → ${period.end_date ?? '…'}`;
}
</script>
