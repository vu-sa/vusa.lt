<template>
  <ul class="divide-y divide-border border-y border-border" data-slot="problem-summary-list">
    <li v-for="problem in problems" :key="problem.id" class="flex min-h-11 items-center gap-3 px-2 py-2.5 sm:px-3">
      <Link
        :href="route('problems.show', problem.id)"
        class="min-w-0 flex-1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
      >
        <span class="block truncate text-sm font-medium text-foreground hover:text-brand">{{ problem.title }}</span>
        <span class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground">
          <span v-if="problem.tenant?.shortname">{{ problem.tenant.shortname }}</span>
          <span v-if="problem.occurred_at" class="tabular-nums">{{ formatDate(new Date(problem.occurred_at)) }}</span>
          <span v-if="problem.responsible_user?.name">{{ problem.responsible_user.name }}</span>
        </span>
      </Link>
      <StatusBadge v-if="statusOf(problem)" :status="statusOf(problem)!" class="shrink-0" />
      <Button
        v-if="removable"
        variant="ghost"
        size="sm"
        voice="sentence"
        class="shrink-0 pointer-coarse:h-11"
        :aria-label="$t('Atsieti problemą')"
        data-testid="problem-unlink"
        @click="$emit('remove', problem)"
      >
        <Unlink class="size-4" aria-hidden="true" />
        <span class="sr-only sm:not-sr-only">{{ $t('Atsieti') }}</span>
      </Button>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Unlink } from 'lucide-vue-next';

import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { problemStatuses, type ProblemStatus } from '@/Constants/statuses';
import { formatDate } from '@/Utils/dateTime';

/** `ProblemSummaryResource`: a problem as a line on another record. */
export interface ProblemSummary {
  id: string;
  title: string;
  status: string;
  occurred_at?: string | null;
  resolved_at?: string | null;
  tenant?: { id: number; shortname: string } | null;
  responsible_user?: { id: string; name: string } | null;
}

defineProps<{ problems: ProblemSummary[]; removable?: boolean }>();
defineEmits<{ remove: [problem: ProblemSummary] }>();

const statusOf = (problem: ProblemSummary) => problemStatuses[problem.status as ProblemStatus];
</script>
