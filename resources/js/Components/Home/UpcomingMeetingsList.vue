<template>
  <OverviewSection
    data-tour="meetings-card"
    :title="$t('Artimiausi posėdžiai')"
    :icon="CalendarDays"
    variant="home"
    content-ruled
    :empty="meetings.length === 0"
    :empty-text="$t('artimiausiu metu nieko nesuplanuota')"
    :href="hasMore ? undefined : href"
    :href-label="$t('Visi posėdžiai')"
  >
    <!-- One header action: the dialog when the list is cut, which itself links to every meeting. -->
    <template v-if="hasMore" #actions>
      <SectionHeaderAction
        :label="$t('Rodyti visus (:count)', { count: String(allCount) })"
        trailing-chevron
        data-slot="upcoming-meetings-more"
        @click="dialogOpen = true"
      />
    </template>

    <CollectionRowList :items="visibleMeetings" frame="box" :item-key="meeting => meeting.id" data-slot="upcoming-meetings">
      <template #default="{ item: meeting }">
        <UpcomingMeetingRow :meeting />
      </template>
    </CollectionRowList>

    <template v-if="hasMore">

      <Dialog v-model:open="dialogOpen">
        <DialogContent class="flex max-h-[85vh] flex-col gap-4 sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle>{{ $t('Artimiausi posėdžiai') }} · {{ allCount }}</DialogTitle>
            <DialogDescription class="sr-only">
              {{ $t('Ateinančių dviejų mėnesių posėdžiai') }}
            </DialogDescription>
          </DialogHeader>
          <div class="min-h-0 flex-1 overflow-y-auto" data-slot="upcoming-meetings-dialog-list">
            <CollectionRowList :items="meetings" frame="box" :item-key="meeting => meeting.id">
              <template #default="{ item: meeting }">
                <UpcomingMeetingRow :meeting />
              </template>
            </CollectionRowList>
          </div>
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
import { CalendarDays } from '@lucide/vue';
import { computed, ref } from 'vue';

import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import SectionHeaderAction from '@/Components/Patterns/SectionHeaderAction.vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

import UpcomingMeetingRow from './UpcomingMeetingRow.vue';
import type { HomeMeeting } from './types';

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
const hasMore = computed(() => props.meetings.length > props.limit);
const allCount = computed(() => Math.max(props.total ?? 0, props.meetings.length));
</script>
