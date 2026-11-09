<template>
  <div class="flex flex-col gap-10" data-slot="institution-overview">
    <OverviewSection
      variant="home"
      :title="upcomingMeeting ? $t('Posėdžiai') : $t('Paskutiniai posėdžiai')"
      :icon="CalendarIcon"
      content-ruled
      :empty="noMeetings"
      :empty-text="$t('Šiai institucijai dar nėra suplanuota susitikimų.')"
    >
      <template v-if="displayMeetings.length > 0 && meetingsTab" #actions>
        <SectionHeaderAction :label="$t('Visi posėdžiai')" trailing-chevron @click="$emit('navigate-tab', 'meetings')" />
      </template>

      <!-- Cards from md; below it a swipeable row, so three cards never make the phone overview three screens long. -->
      <ul
        v-if="displayMeetings.length > 0"
        :class="[
          'flex snap-x snap-mandatory gap-3 overflow-x-auto pb-1 [scrollbar-width:thin]',
          'md:grid md:grid-cols-3 md:gap-4 md:overflow-visible md:pb-0',
        ]"
        data-slot="institution-recent-meetings"
      >
        <li v-for="meeting in displayMeetings" :key="meeting.id" class="w-[85%] max-w-80 shrink-0 snap-start md:w-auto md:max-w-none">
          <MeetingCard :meeting :href="route('meetings.show', meeting.id)" />
        </li>
      </ul>
      <EmptyState
        v-else-if="overview.meetings_hidden"
        :title="$t('Posėdžiai nėra vieši')"
        :description="hiddenMeetingsDescription"
        :icon="Lock"
        data-testid="institution-meetings-hidden"
      />
    </OverviewSection>

    <div
      v-if="duties || secretaries.length"
      :class="['grid gap-10', duties && secretaries.length && 'xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)] xl:gap-16']"
    >
      <InstitutionDutiesSection
        v-if="duties"
        :duties
        :total-count="institution.duties_count"
        preview
        @view-all="$emit('navigate-tab', 'duties')"
        :institution-id="institution.id"
        :can-manage="false"
        @assign="$emit('assign', $event)"
        @edit-term="(duty, user) => $emit('edit-term', duty, user)"
      />

      <!-- Nominated for the current term (O22). Distinct from the body's members: a secretary
           need not hold a duty here at all. -->
      <OverviewSection v-if="secretaries.length" variant="home" :title="$t('secretaries.label')" :icon="UserCheck">
        <PeopleContactList :people="secretaries" compact />
      </OverviewSection>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Calendar as CalendarIcon, Lock, UserCheck } from '@lucide/vue';

import PeopleContactList from '@/Components/Avatars/PeopleContactList.vue';
import MeetingCard from '@/Components/Meetings/MeetingCard.vue';
import { EmptyState, OverviewSection, SectionHeaderAction } from '@/Components/Patterns';
import type { DutyWithUsers, UserWithPivot } from '@/Types/DutyAssignment';
import type { InstitutionOverviewData, InstitutionPageData } from '@/Types/InstitutionPage';

import InstitutionDutiesSection from './InstitutionDutiesSection.vue';

const props = defineProps<{
  institution: InstitutionPageData;
  overview: InstitutionOverviewData;
  /** Bounded preview; the full list lives in Pareigybės. */
  duties?: DutyWithUsers[] | null;
  canManage?: boolean;
  /** Whether a Posėdžiai tab exists for "Visi posėdžiai" to open. */
  meetingsTab?: boolean;
  upcomingMeeting?: import('@/Types/InstitutionPage').InstitutionPageMeeting | null;
}>();

defineEmits<{
  'navigate-tab': [tab: string];
  'assign': [duty: DutyWithUsers];
  'edit-term': [duty: DutyWithUsers, user: UserWithPivot];
}>();

const secretaries = computed(() => props.institution.secretaries ?? []);
const hiddenMeetingsDescription = computed(() => $t(
  'Šios institucijos posėdžiai nėra vieši, todėl jų čia nematai. Jei reikia informacijos apie posėdžius, susisiek su institucijos nariais ar koordinatoriais – jie nurodyti viršuje.',
));

const recentMeetings = computed(() => [...props.overview.recentMeetings]
  .sort((a, b) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime())
  .slice(0, 3));

const displayMeetings = computed(() => [
  ...(props.upcomingMeeting ? [props.upcomingMeeting] : []),
  ...recentMeetings.value.slice(0, props.upcomingMeeting ? 2 : 3),
]);

const noMeetings = computed(() => displayMeetings.value.length === 0 && !props.overview.meetings_hidden);
</script>
