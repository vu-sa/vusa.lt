<template>
  <div class="meeting-page">
    <PageTitleBand :title="formatMeetingDateTime(meeting)" :lead="meeting.description || undefined" size="md">
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>
      <template #eyebrow>
        <InertiaLink :href="institutionUrl" class="transition-colors hover:text-foreground">
          {{ institution.name }}
        </InertiaLink>
      </template>
      <template v-if="requiresStudentPerspective" #actions>
        <Button variant="outline" size="sm" @click="showInfoModal = true">
          <IFluentInfo16Regular class="size-4" />
          {{ $t('Apie balsavimo skaidrumą') }}
        </Button>
      </template>
    </PageTitleBand>

    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
      <div class="max-w-3xl space-y-10">
        <div class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
          <span>
            {{ allAgendaItems.length }}
            {{ allAgendaItems.length === 1 ? $t('klausimas') : $t('klausimai') }}
          </span>
          <AgendaOutcomeIndicators v-if="requiresStudentPerspective" :agenda-items="itemsWithDecisions" />
        </div>

        <div>
          <EyebrowLabel as="h2" class="mb-3 text-muted-foreground">
            {{ $t('Studentų atstovai') }}
          </EyebrowLabel>
          <ul v-if="representatives.length > 0" class="flex flex-wrap gap-x-6 gap-y-3">
            <li v-for="user in representatives" :key="user.id" class="flex items-center gap-2.5">
              <img
                v-if="user.profile_photo_path"
                :src="user.profile_photo_path"
                alt=""
                class="size-8 border border-border object-cover"
                loading="lazy"
              >
              <span class="text-sm text-foreground">{{ user.name }}</span>
            </li>
          </ul>
          <p v-else class="text-sm text-muted-foreground">
            {{ $t('Atstovai nežinomi') }}
          </p>
        </div>

        <section v-if="requiresStudentPerspective && outcomeSummary.total > 0" data-testid="outcome-summary">
          <h2 :class="sectionHeadingClass">
            {{ $t('Sprendimų santrauka') }}
          </h2>
          <div class="grid grid-cols-3 divide-x divide-border border-y border-border">
            <StatCell :value="outcomeSummary.positive" :label="$t('Teigiami')" class="px-4 py-5 first:pl-0" />
            <StatCell :value="outcomeSummary.neutral" :label="$t('Neutralūs')" class="px-4 py-5" />
            <StatCell :value="outcomeSummary.negative" :label="$t('Neigiami')" class="px-4 py-5" />
          </div>
          <p v-if="outcomeSummary.alignedCount > 0" class="mt-3 text-sm text-muted-foreground">
            {{ $t('Studentų pozicijos atitiko sprendimą') }}:
            <span class="font-semibold text-foreground">
              {{ outcomeSummary.alignedCount }} / {{ outcomeSummary.comparableCount }}
              ({{ outcomeSummary.alignmentRate }}%)
            </span>
          </p>
        </section>

        <section>
          <h2 :class="sectionHeadingClass">
            {{ $t('Darbotvarkė') }}
          </h2>
          <PublicAgendaList
            :items="allAgendaItems"
            :requires-student-perspective
            :is-upcoming
            :show-heading="false"
          />
        </section>

        <section v-if="documents.length">
          <h2 :class="sectionHeadingClass">
            {{ $t('Dokumentai') }}
          </h2>
          <PublicMeetingDocuments :documents="documents" />
        </section>

        <nav
          v-if="previousMeeting || nextMeeting"
          class="flex items-center justify-between gap-4 border-t border-border pt-6"
        >
          <InertiaLink v-if="previousMeeting" :href="meetingUrl(previousMeeting.id)" class="group flex items-center gap-3">
            <IFluentArrowLeft20Regular class="size-4 shrink-0 text-muted-foreground transition-colors group-hover:text-brand" />
            <div class="text-left">
              <span class="block font-mono text-xs uppercase tracking-wider text-muted-foreground">{{ $t('Ankstesnis posėdis') }}</span>
              <span class="block text-sm font-medium text-foreground transition-colors group-hover:text-brand">
                {{ formatMeetingDateTime(previousMeeting) }}
              </span>
            </div>
          </InertiaLink>
          <div v-else />

          <InertiaLink v-if="nextMeeting" :href="meetingUrl(nextMeeting.id)" class="group ml-auto flex items-center gap-3">
            <div class="text-right">
              <span class="block font-mono text-xs uppercase tracking-wider text-muted-foreground">{{ $t('Kitas posėdis') }}</span>
              <span class="block text-sm font-medium text-foreground transition-colors group-hover:text-brand">
                {{ formatMeetingDateTime(nextMeeting) }}
              </span>
            </div>
            <IFluentArrowRight20Regular class="size-4 shrink-0 text-muted-foreground transition-colors group-hover:text-brand" />
          </InertiaLink>
        </nav>
      </div>
    </section>

    <PublicVotingExplainerModal v-model:open="showInfoModal" />

    <FeedbackPopover />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage, Link as InertiaLink } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { formatMeetingDateTime } from '@/Utils/MeetingDisplay';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { getMainVote, getMeetingStatusSummary, hasDecisionData } from '@/Composables/useAgendaItemStyling';
import AgendaOutcomeIndicators from '@/Components/Public/AgendaOutcomeIndicators.vue';
import FeedbackPopover from '@/Components/Public/FeedbackPopover.vue';
import PublicAgendaList from '@/Components/Public/PublicAgendaList.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import PublicMeetingDocuments, { type PublicMeetingDocument } from '@/Components/Public/PublicMeetingDocuments.vue';
import PublicVotingExplainerModal from '@/Components/Public/PublicVotingExplainerModal.vue';
import { EyebrowLabel, PageTitleBand, StatCell } from '@/Components/Public/Base';
import { Button } from '@/Components/ui/button';
import IFluentArrowLeft20Regular from '~icons/fluent/arrow-left-20-regular';
import IFluentArrowRight20Regular from '~icons/fluent/arrow-right-20-regular';
import IFluentInfo16Regular from '~icons/fluent/info-16-regular';
import IFluentPeople24Regular from '~icons/fluent/people-24-regular';

const props = withDefaults(defineProps<{
  meeting: App.Entities.Meeting;
  institution: App.Entities.Institution;
  representatives: App.Entities.User[];
  previousMeeting?: { id: string; start_time: string; type?: string | null } | null;
  nextMeeting?: { id: string; start_time: string; type?: string | null } | null;
  documents?: PublicMeetingDocument[];
  /** False for VU SA's own bodies — see Meeting::requiresStudentPerspective(). */
  requiresStudentPerspective?: boolean;
  calendarEvent?: { id: number; title: string; date: string } | null;
}>(), {
  previousMeeting: null,
  nextMeeting: null,
  documents: () => [],
  requiresStudentPerspective: true,
  calendarEvent: null,
});

const page = usePage();
const showInfoModal = ref(false);

const sectionHeadingClass = 'u-display mb-4 border-l-2 border-brand pl-3 text-lg font-bold tracking-tight text-foreground sm:text-xl';

const isUpcoming = computed(() => new Date(props.meeting.start_time) > new Date());
const allAgendaItems = computed(() => props.meeting.agenda_items || []);
const itemsWithDecisions = computed(() => allAgendaItems.value.filter(hasDecisionData));

const routeContext = computed(() => ({
  lang: page.props.app.locale,
  subdomain: page.props.tenant?.subdomain || 'www',
}));

const institutionUrl = computed(() => route('contacts.institution', { institution: props.institution.id, ...routeContext.value }));
const meetingUrl = (id: string) => route('publicMeetings.show', { meeting: id, ...routeContext.value });

const outcomeSummary = computed(() => {
  const summary = getMeetingStatusSummary(allAgendaItems.value, props.requiresStudentPerspective);
  const counts = { positive: 0, negative: 0, neutral: 0 };

  for (const item of allAgendaItems.value) {
    const decision = getMainVote(item)?.decision;
    if (decision === 'positive' || decision === 'negative' || decision === 'neutral') {
      counts[decision]++;
    }
  }

  return {
    ...counts,
    total: counts.positive + counts.negative + counts.neutral,
    alignedCount: summary.aligned,
    comparableCount: summary.aligned + summary.misaligned,
    alignmentRate: summary.alignmentRate,
  };
});

usePageBreadcrumbs(() => [
  BreadcrumbHelpers.createRouteBreadcrumb(
    props.institution.name,
    'contacts.institution',
    { institution: props.institution.id, ...routeContext.value },
    IFluentPeople24Regular,
  ),
  BreadcrumbHelpers.createBreadcrumbItem(formatMeetingDateTime(props.meeting), undefined),
], { placement: 'band' });
</script>
