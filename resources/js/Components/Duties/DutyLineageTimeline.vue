<template>
  <ol class="relative space-y-4 border-l border-border pl-5" data-slot="duty-lineage-timeline">
    <li v-for="event in events" :key="`${event.kind}-${event.date}`" class="relative" :data-testid="`lineage-event-${event.kind}`">
      <span class="absolute -left-[1.4rem] top-1.5 size-2 bg-muted-foreground/60" aria-hidden="true" />
      <p class="text-xs font-semibold text-muted-foreground tabular-nums">
        {{ event.kind === 'rename' ? $t('Iki') : $t('Nuo') }} {{ formatDate(event.date) }}
      </p>

      <p v-if="event.kind === 'rename'" class="mt-0.5 text-sm">
        {{ $t('Vadinosi') }} „{{ event.name }}“
      </p>

      <template v-else>
        <p class="mt-0.5 text-sm">
          {{ headline(event) }}
        </p>
        <ul class="mt-1">
          <li v-for="linked in event.duties" :key="linked.link_id" class="flex min-h-11 flex-wrap items-center justify-between gap-x-2">
            <Link :href="route('duties.show', linked.id)" class="min-w-0 truncate font-medium hover:underline">
              <InflectedDutyName :name="linked.name" />
              <span v-if="linked.institution_name" class="font-normal text-muted-foreground"> · {{ linked.institution_name }}</span>
            </Link>
            <span class="flex shrink-0 items-center gap-1">
              <StatusBadge v-if="!linked.is_active" :status="operatingStatuses.inactive" />
              <Button
                v-if="linked.can_fold"
                size="sm"
                variant="ghost"
                voice="sentence"
                class="u-touch"
                data-testid="lineage-fold"
                @click="emit('fold', linked)"
              >
                <Merge class="size-4" aria-hidden="true" />
                {{ $t('Tai buvo tik pervadinimas') }}
              </Button>
              <Button
                v-if="canManage && event.kind === 'took_over'"
                size="sm"
                variant="ghost"
                voice="sentence"
                class="u-touch"
                :aria-label="$t('Atsieti')"
                data-testid="lineage-unlink"
                @click="emit('unlink', linked)"
              >
                <X class="size-4" aria-hidden="true" />
              </Button>
            </span>
          </li>
        </ul>
      </template>
    </li>
  </ol>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Merge, X } from '@lucide/vue';

import InflectedDutyName from './InflectedDutyName.vue';

import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { operatingStatuses } from '@/Constants/statuses';

export interface LineageLinkedDuty {
  id: string;
  link_id: number;
  name: string;
  is_active: boolean;
  institution_name: string | null;
  can_fold?: boolean;
}

export type DutyLineageEvent
  = | { kind: 'rename'; date: string; name: string | null }
    | { kind: 'took_over' | 'handed_over'; date: string; duties: LineageLinkedDuty[] };

defineProps<{ events: DutyLineageEvent[]; canManage: boolean }>();
const emit = defineEmits<{ unlink: [duty: LineageLinkedDuty]; fold: [duty: LineageLinkedDuty] }>();

const { formatDate } = useDateFormatter();

// Merge or split is read from how many duties share the day, never stored.
const headline = (event: Extract<DutyLineageEvent, { duties: LineageLinkedDuty[] }>) => {
  const several = event.duties.length > 1;

  return event.kind === 'took_over'
    ? (several ? $t('Sujungta iš šių pareigybių:') : $t('Perėmė darbą iš'))
    : (several ? $t('Padalinta į šias pareigybes:') : $t('Darbą perėmė'));
};
</script>
