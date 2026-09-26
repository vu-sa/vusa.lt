<template>
  <OverviewSection
    data-tour="meetings-card"
    :title="$t('Artimiausi posėdžiai')"
    :icon="CalendarDays"
    variant="home"
    :empty="meetings.length === 0"
    :empty-text="$t('artimiausiu metu nieko nesuplanuota')"
    :href
    :href-label="$t('Visi posėdžiai')"
  >
    <ul class="divide-y divide-border/60" data-slot="upcoming-meetings">
      <li v-for="meeting in visibleMeetings" :key="meeting.id">
        <UpcomingMeetingRow :meeting />
      </li>
    </ul>

    <template v-if="meetings.length > limit">
      <Button
        variant="outline"
        size="sm"
        class="self-start pointer-coarse:h-11"
        data-slot="upcoming-meetings-more"
        @click="dialogOpen = true"
      >
        <List aria-hidden="true" />
        {{ $t('Rodyti visus (:count)', { count: String(allCount) }) }}
      </Button>

      <Dialog v-model:open="dialogOpen">
        <DialogContent class="flex max-h-[85vh] flex-col gap-4 sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle>{{ $t('Artimiausi posėdžiai') }} · {{ allCount }}</DialogTitle>
            <DialogDescription class="sr-only">
              {{ $t('Ateinančių dviejų mėnesių posėdžiai') }}
            </DialogDescription>
          </DialogHeader>
          <ul class="-mx-6 min-h-0 flex-1 divide-y divide-border/60 overflow-y-auto px-6" data-slot="upcoming-meetings-dialog-list">
            <li v-for="meeting in meetings" :key="meeting.id">
              <UpcomingMeetingRow :meeting />
            </li>
          </ul>
          <!-- The payload is capped; the rest live in the meetings collection. -->
          <Link v-if="href && allCount > meetings.length" :href class="text-sm underline underline-offset-4">
            {{ $t('Visi posėdžiai') }}
          </Link>
        </DialogContent>
      </Dialog>
    </template>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarDays, List } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import type { HomeMeeting } from './types';
import UpcomingMeetingRow from './UpcomingMeetingRow.vue';

import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

const props = withDefaults(defineProps<{
  meetings: HomeMeeting[];
  /** Every upcoming meeting, when the server capped `meetings`. */
  total?: number;
  href?: string;
  /** Rows shown on the page; the rest open in a dialog. */
  limit?: number;
}>(), {
  total: undefined,
  href: undefined,
  limit: 3,
});

const dialogOpen = ref(false);
const visibleMeetings = computed(() => props.meetings.slice(0, props.limit));
const allCount = computed(() => Math.max(props.total ?? 0, props.meetings.length));
</script>
