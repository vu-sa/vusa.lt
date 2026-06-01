<template>
  <AdminContentPage>
    <InertiaHead :title="pageTitle" />

    <ShowPageHero :title="agendaItem.title" :badge="badge">
      <template #icon>
        <ClipboardList class="h-6 w-6 sm:h-7 sm:w-7 text-zinc-600 dark:text-zinc-300" />
      </template>
      <template v-if="meetingLabel" #subtitle>
        <Link
          v-if="agendaItem.meeting_id"
          :href="route('meetings.show', agendaItem.meeting_id)"
          class="inline-flex items-center gap-1.5 hover:text-primary transition-colors"
        >
          <CalendarDays class="h-4 w-4" />
          {{ meetingLabel }}
        </Link>
      </template>
    </ShowPageHero>

    <div class="mt-6 max-w-2xl">
      <AgendaItemEditor :agenda-item="agendaItem" />
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, Head as InertiaHead } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarDays, ClipboardList } from 'lucide-vue-next';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import ShowPageHero from '@/Components/Hero/ShowPageHero.vue';
import AgendaItemEditor from '@/Components/AgendaItems/AgendaItemEditor.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { formatStaticTime } from '@/Utils/IntlTime';
import { MeetingIconFilled } from '@/Components/icons';

const props = defineProps<{
  agendaItem: App.Entities.AgendaItem;
}>();

const meetingLabel = computed(() => {
  const meeting = props.agendaItem.meeting;
  if (!meeting) { return ''; }
  if (meeting.start_time) {
    return formatStaticTime(new Date(meeting.start_time), {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  }
  return meeting.title ?? '';
});

const pageTitle = computed(() => `${$t('Redaguoti')}: ${props.agendaItem.title}`);

const badge = computed(() => ({
  label: $t('Darbotvarkės punktas'),
  variant: 'secondary' as const,
}));

usePageBreadcrumbs(() => {
  const items = [];

  if (props.agendaItem.meeting_id) {
    items.push(BreadcrumbHelpers.createRouteBreadcrumb(
      meetingLabel.value || $t('Posėdis'),
      'meetings.show',
      { meeting: props.agendaItem.meeting_id },
      MeetingIconFilled,
    ));
  }

  items.push(BreadcrumbHelpers.createBreadcrumbItem(props.agendaItem.title));
  return items;
});
</script>
