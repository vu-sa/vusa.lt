<template>
  <div class="calendar-event-page">
    <!-- Hero: inset card inside the layout's wrapper — no full-bleed -->
    <EventHero :event :is-meeting="!!meeting">
      <template #actions="{ onImage }">
        <EventActions
          :registration-url="registrationUrl"
          :facebook-url="event.facebook_url"
          :share-title="eventTitle"
          :is-past="isPast"
          :is-live="isLive"
          :on-image="onImage"
        />
      </template>
    </EventHero>

    <!-- Main Content Area -->
    <div class="mt-8 lg:mt-12">
      <!-- Two Column Layout -->
      <div class="grid lg:grid-cols-12 gap-8 lg:gap-12">
        <!-- Main Content -->
        <main class="order-last space-y-10 lg:order-none lg:col-span-8">
          <!-- Description: no heading needed -->
          <div
            v-if="event.description"
            class="typography max-w-none text-zinc-700 dark:text-zinc-300"
            v-html="event.description"
          />

          <!-- Agenda: present only when this event stands for a meeting. -->
          <section v-if="meeting">
            <div class="flex items-center gap-4 mb-4">
              <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $t("Darbotvarkė") }}
              </h2>
              <div class="h-px flex-1 bg-gradient-to-r from-zinc-200 to-transparent dark:from-zinc-700" />
            </div>
            <PublicAgendaList
              :items="meeting.agenda_items"
              :requires-student-perspective="meeting.requires_student_perspective"
              :is-upcoming="!isPast"
              :show-heading="false"
            />
            <InertiaLink
              v-if="meeting.is_publicly_visible"
              :href="meetingUrl"
              class="mt-4 inline-flex items-center gap-1.5 text-sm text-zinc-500 transition-colors hover:text-primary-600 dark:text-zinc-400 dark:hover:text-primary-400"
            >
              {{ $t("Posėdžio puslapis") }}
              <IFluentArrowRight20Regular class="h-4 w-4" />
            </InertiaLink>

            <!-- Sibling announcements for the same institution -->
            <nav v-if="previousMeetingEvent || nextMeetingEvent" class="mt-6 flex items-center justify-between gap-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
              <InertiaLink
                v-if="previousMeetingEvent"
                :href="getCalendarEvent2Route(previousMeetingEvent, locale)"
                class="group flex items-center gap-3"
              >
                <IFluentArrowLeft20Regular class="h-4 w-4 shrink-0 text-zinc-400 transition-colors group-hover:text-primary-600 dark:text-zinc-500 dark:group-hover:text-primary-400" />
                <div class="text-left">
                  <span class="block text-xs uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ $t('Ankstesnis posėdis') }}</span>
                  <span class="block text-sm font-medium text-zinc-600 transition-colors group-hover:text-primary-600 dark:text-zinc-400 dark:group-hover:text-primary-400">
                    {{ siblingEventDate(previousMeetingEvent) }}
                  </span>
                </div>
              </InertiaLink>
              <div v-else />

              <InertiaLink
                v-if="nextMeetingEvent"
                :href="getCalendarEvent2Route(nextMeetingEvent, locale)"
                class="group ml-auto flex items-center gap-3"
              >
                <div class="text-right">
                  <span class="block text-xs uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ $t('Kitas posėdis') }}</span>
                  <span class="block text-sm font-medium text-zinc-600 transition-colors group-hover:text-primary-600 dark:text-zinc-400 dark:group-hover:text-primary-400">
                    {{ siblingEventDate(nextMeetingEvent) }}
                  </span>
                </div>
                <IFluentArrowRight20Regular class="h-4 w-4 shrink-0 text-zinc-400 transition-colors group-hover:text-primary-600 dark:text-zinc-500 dark:group-hover:text-primary-400" />
              </InertiaLink>
            </nav>
          </section>

          <section v-if="meeting?.documents?.length">
            <div class="flex items-center gap-4 mb-4">
              <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $t("Dokumentai") }}
              </h2>
              <div class="h-px flex-1 bg-gradient-to-r from-zinc-200 to-transparent dark:from-zinc-700" />
            </div>
            <PublicMeetingDocuments :documents="meeting.documents" />
          </section>

          <!-- Video Section -->
          <section v-if="event.video_url">
            <div class="flex items-center gap-4 mb-4">
              <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $t("Video") }}
              </h2>
              <div class="h-px flex-1 bg-gradient-to-r from-zinc-200 to-transparent dark:from-zinc-700" />
            </div>
            <div class="overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800 ring-1 ring-zinc-900/5 dark:ring-white/10">
              <iframe
                class="aspect-video w-full"
                :src="`https://www.youtube-nocookie.com/embed/${event.video_url}`"
                title="YouTube video player"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
              />
            </div>
          </section>

          <!-- Image Gallery Section -->
          <section v-if="normalizedImages.length > 1">
            <EventImageGallery :images="normalizedImages" :event-title="eventTitle" />
          </section>

          <!-- Other events: below the description/agenda, not tucked in the sidebar -->
          <section v-if="otherEvents.length">
            <div class="flex items-center gap-4 mb-4">
              <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $t("Kiti renginiai") }}
              </h2>
              <div class="h-px flex-1 bg-gradient-to-r from-zinc-200 to-transparent dark:from-zinc-700" />
            </div>
            <div class="flex flex-col gap-2">
              <EventListRow v-for="otherEvent in otherEvents" :key="otherEvent.id" :event="otherEvent" />
            </div>
          </section>
        </main>

        <!-- Sidebar. Ordered first on a phone: when the event is (and where) is what a
             visitor came for, and below a long description it was never seen. -->
        <aside class="order-first lg:order-none lg:col-span-4">
          <!-- top-28 clears the fixed main navigation (see MainNavigation.vue) -->
          <div class="lg:sticky lg:top-28">
            <EventDetailsCard
              :event
              :google-link
              :coordinates="eventLocation"
            />
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { usePage, Link as InertiaLink } from '@inertiajs/vue3';

import EventActions from '@/Components/Calendar/EventActions.vue';
import EventDetailsCard from '@/Components/Calendar/EventDetailsCard.vue';
import EventHero from '@/Components/Calendar/EventHero.vue';
import EventImageGallery from '@/Components/Calendar/EventImageGallery.vue';
import EventListRow from '@/Components/Calendar/EventListRow.vue';
import PublicAgendaList from '@/Components/Public/PublicAgendaList.vue';
import PublicMeetingDocuments, { type PublicMeetingDocument } from '@/Components/Public/PublicMeetingDocuments.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { useEventStatus } from '@/Composables/useEventStatus';
import { formatStaticTime } from '@/Utils/IntlTime';
import { getCalendarEvent2Route } from '@/Utils/Route';
import { LocaleEnum } from '@/Types/enums';
import IFluentArrowLeft20Regular from '~icons/fluent/arrow-left-20-regular';
import IFluentArrowRight20Regular from '~icons/fluent/arrow-right-20-regular';

const props = defineProps<{
  event: App.Entities.Calendar;
  calendar: App.Entities.Calendar[];
  googleLink: string;
  eventLocation: { lat: number; lng: number; display_name: string } | null;
  /**
   * Set only when this event announces a meeting — the agenda and the paperwork then live on
   * the same page as the announcement instead of three places at once.
   */
  meeting?: {
    id: string;
    start_time: string;
    agenda_items: App.Entities.AgendaItem[];
    requires_student_perspective: boolean;
    documents: PublicMeetingDocument[];
    institution: { id: string; name: string; alias: string } | null;
    /** Settings-only — see Meeting::isPubliclyVisible(). A published event still shows the
     * agenda above regardless; this only gates the link through to the meeting page. */
    is_publicly_visible: boolean;
  } | null;
  /** Nearest published announcements for the same institution, before/after this one. */
  previousMeetingEvent?: { id: number; title: string; date: string } | null;
  nextMeetingEvent?: { id: number; title: string; date: string } | null;
}>();

const page = usePage();
const locale = computed(() => page.props.app.locale);

const eventTitle = computed(() =>
  Array.isArray(props.event.title) ? props.event.title.join(' ') : String(props.event.title ?? ''),
);

// Set up breadcrumbs
usePageBreadcrumbs(() =>
  BreadcrumbHelpers.publicContent([
    { label: 'Kalendorius', href: route('calendar.list', { lang: locale.value }) },
    { label: eventTitle.value },
  ]),
);

const { isPast, isLive } = useEventStatus(() => props.event, () => !!props.meeting);

const meeting = computed(() => props.meeting ?? null);

const meetingUrl = computed(() => route('publicMeetings.show', {
  meeting: meeting.value?.id,
  lang: locale.value,
  subdomain: page.props.tenant?.subdomain || 'www',
}));

/** The call-to-action URL is stored as `cto_url`, not `url`. */
const registrationUrl = computed(() => (props.event.cto_url ? String(props.event.cto_url) : null));

/** The server already hands these over soonest-first; the filter only guards against this
 *  event slipping into its own list. */
const otherEvents = computed(() =>
  props.calendar.filter(other => other.id !== props.event.id).slice(0, 4),
);

/** Full date + time for a sibling announcement, so the prev/next links say when, not just which. */
const siblingEventDate = (event: { date: string }): string =>
  formatStaticTime(new Date(event.date), {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }, locale.value as LocaleEnum);

// Normalize images to array format for EventImageGallery
const normalizedImages = computed(() => {
  const { images } = props.event as { images?: unknown };
  if (!images) return [];
  if (Array.isArray(images)) return images;
  if (typeof images === 'object') return Object.values(images);
  return [];
});
</script>
