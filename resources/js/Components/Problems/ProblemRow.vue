<template>
  <CollectionRow :actions :spotlight :class="ROW_PADDING_CLASS" data-slot="problem-row" @select="emit('select', $event)">
    <div class="flex min-w-0 items-center gap-2">
      <Link
        v-if="href"
        :href
        data-collection-open
        class="truncate font-bold text-foreground hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
      >
        {{ title }}
      </Link>
      <span v-else class="truncate font-bold text-muted-foreground">{{ title }}</span>
      <StatusBadge v-if="showStatus && status" :status class="shrink-0" />
    </div>
    <p v-if="facts.length" class="mt-0.5 truncate text-xs text-muted-foreground">
      {{ facts.join(' · ') }}
    </p>
  </CollectionRow>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import { ROW_PADDING_CLASS } from '@/Components/Collection/rowList';
import { StatusBadge } from '@/Components/Patterns';
import { problemStatuses, type ProblemStatus } from '@/Constants/statusIcons';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';
import { formatDate } from '@/Utils/dateTime';

/** A problem as one row: the collection and every record that lists problems share it. */
const props = withDefaults(defineProps<{
  problem: {
    status?: string | null;
    occurred_at?: string | null;
    tenant?: { shortname?: string | null } | null;
    responsible_user?: { name?: string | null } | null;
  };
  /** Localised by the caller: the index has both languages, a summary has one. */
  title: string;
  /** None for a trashed problem, which cannot be opened. */
  href?: string;
  actions?: AdminRecordAction[];
  spotlight?: boolean;
  /** Off beside the detail card, which shows the status itself. */
  showStatus?: boolean;
}>(), {
  href: undefined,
  actions: () => [],
  showStatus: true,
});

const emit = defineEmits<{ select: [key: string] }>();

const status = computed(() => problemStatuses[props.problem.status as ProblemStatus]);

const facts = computed(() => [
  props.problem.tenant?.shortname,
  props.problem.occurred_at ? formatDate(new Date(props.problem.occurred_at)) : null,
  props.problem.responsible_user?.name,
].filter((part): part is string => Boolean(part)));
</script>
