<template>
  <div class="calendar-event-page">
    <!-- Hero: full-bleed editorial hero with breadcrumbs, date plate, tag, and display title -->
    <EventHero
      :event
      :is-meeting="!!meeting"
    >
      <template #actions>
        <EventActions
          :registration-url
          :facebook-url="event.facebook_url"
          :share-title="eventTitle"
          :is-past
          :is-live
        />
      </template>
    </EventHero>

    <!-- Main Content Area: rc-viewport escapes .wrapper's 1200px column so the
         grid aligns with the 1280px (max-w-7xl) hero and related-events sections. -->
    <div class="rc-viewport">
      <div class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8 lg:py-16">
        <!-- Two Column Layout: 20rem sidebar matches v0 reference (.design-reference/v0/app/renginiai/[slug]/page.tsx) -->
        <div class="grid gap-10 lg:grid-cols-[1fr_20rem] lg:gap-14">
          <!-- Main Content -->
          <main class="order-last min-w-0 space-y-10 lg:order-none">
            <!-- Description / Rich Content -->
            <article
              v-if="event.description"
              class="rc-prose max-w-none"
              v-html="event.description"
            />

            <!-- Agenda: present only when this event stands for a meeting. -->
            <section v-if="meeting">
              <div class="mb-4 flex items-baseline gap-3 border-l-2 border-brand pl-3">
                <h2 class="u-display text-lg font-bold tracking-tight text-foreground sm:text-xl">
                  {{ $t("Darbotvarkė") }}
                </h2>
              </div>
              <PublicAgendaList
                :items="meeting.agenda_items"
                :requires-student-perspective="meeting.requires_student_perspective"
                :is-upcoming="!isPast"
                :show-heading="false"
              />
              <Link
                v-if="meeting.is_publicly_visible"
                :href="meetingUrl"
                class="mt-4 inline-flex items-center gap-1.5 font-mono text-xs font-bold uppercase tracking-wider text-muted-foreground transition-colors hover:text-brand"
              >
                {{ $t("Posėdžio puslapis") }}
                <IFluentArrowRight20Regular class="size-4" />
              </Link>

              <!-- Sibling announcements for the same institution -->
              <nav
                v-if="previousMeetingEvent || nextMeetingEvent"
                class="mt-6 flex items-center justify-between gap-4 border-t border-border pt-4"
              >
                <Link
                  v-if="previousMeetingEvent"
                  :href="getCalendarEvent2Route(previousMeetingEvent, locale)"
                  class="group flex items-center gap-3"
                >
                  <IFluentArrowLeft20Regular class="size-4 shrink-0 text-muted-foreground transition-colors group-hover:text-brand" />
                  <div class="text-left">
                    <span class="block font-mono text-xs uppercase tracking-wider text-muted-foreground">{{ $t('Ankstesnis posėdis') }}</span>
                    <span class="block text-sm font-medium text-foreground transition-colors group-hover:text-brand">
                      {{ siblingEventDate(previousMeetingEvent) }}
                    </span>
                  </div>
                </Link>
                <div v-else />

                <Link
                  v-if="nextMeetingEvent"
                  :href="getCalendarEvent2Route(nextMeetingEvent, locale)"
                  class="group ml-auto flex items-center gap-3"
                >
                  <div class="text-right">
                    <span class="block font-mono text-xs uppercase tracking-wider text-muted-foreground">{{ $t('Kitas posėdis') }}</span>
                    <span class="block text-sm font-medium text-foreground transition-colors group-hover:text-brand">
                      {{ siblingEventDate(nextMeetingEvent) }}
                    </span>
                  </div>
                  <IFluentArrowRight20Regular class="size-4 shrink-0 text-muted-foreground transition-colors group-hover:text-brand" />
                </Link>
              </nav>
            </section>

            <!-- Meeting documents -->
            <section v-if="meeting?.documents?.length">
              <div class="mb-4 flex items-baseline gap-3 border-l-2 border-brand pl-3">
                <h2 class="u-display text-lg font-bold tracking-tight text-foreground sm:text-xl">
                  {{ $t("Dokumentai") }}
                </h2>
              </div>
              <PublicMeetingDocuments :documents="meeting.documents" />
            </section>

            <!-- Video Section -->
            <section v-if="event.video_url">
              <div class="mb-4 flex items-baseline gap-3 border-l-2 border-brand pl-3">
                <h2 class="u-display text-lg font-bold tracking-tight text-foreground sm:text-xl">
                  {{ $t("Video") }}
                </h2>
              </div>
              <div class="overflow-hidden border border-border bg-secondary">
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
              <EventImageGallery :images="normalizedImages" :event-title />
            </section>

            <!-- Article Bottom Action Bar -->
            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-border pt-6">
              <Link
                :href="route('calendar.list', { lang: locale })"
                class="inline-flex items-center gap-2 font-mono text-xs font-bold uppercase tracking-wider text-foreground transition-colors hover:text-brand"
              >
                <IFluentArrowLeft20Regular class="size-4" />
                {{ $t('Visi renginiai') }}
              </Link>
              <Button
                variant="brand-outline"
                size="public"
                @click="handleShare"
              >
                <IFluentShare20Regular class="size-4" />
                <span>{{ $t('Dalintis') }}</span>
              </Button>
            </div>
          </main>

          <!-- Sidebar. Ordered first on a phone: when the event is (and where) is what a
             visitor came for, and below a long description it was never seen. -->
          <aside class="order-first lg:order-none">
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

    <!-- Related events: full-bleed 3-column card grid -->
    <section v-if="otherEvents.length" class="rc-viewport border-t border-border bg-secondary/40">
      <div class="mx-auto max-w-7xl px-5 py-14 sm:px-6 lg:px-8 lg:py-20">
        <div class="flex items-end justify-between border-b border-border pb-5">
          <div>
            <span class="u-eyebrow">{{ $t('Nepraleisk') }}</span>
            <h2 class="u-display mt-2 text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
              {{ $t('Kiti renginiai') }}
            </h2>
          </div>
          <Link
            :href="route('calendar.list', { lang: locale })"
            class="hidden items-center gap-2 font-mono text-xs font-bold uppercase tracking-wider text-foreground transition-colors hover:text-brand sm:inline-flex"
          >
            {{ $t('Visi renginiai') }}
            <IFluentArrowRight20Regular class="size-4" />
          </Link>
        </div>

        <div class="grid gap-x-8 gap-y-10 pt-10 sm:grid-cols-2 lg:grid-cols-3">
          <Link
            v-for="otherEvent in otherEvents"
            :key="otherEvent.id"
            :href="route('calendar.event', { calendar: otherEvent.id, lang: locale })"
            class="group flex flex-col"
          >
            <div class="relative aspect-[16/10] overflow-hidden border border-border bg-secondary">
              <img
                v-if="otherEventImage(otherEvent)"
                :src="otherEventImage(otherEvent)"
                :alt="otherEventTitle(otherEvent)"
                class="h-full w-full object-cover grayscale transition-transform duration-500 group-hover:scale-105"
                loading="lazy"
              >
              <div
                v-else
                class="flex h-full w-full items-center justify-center bg-secondary text-muted-foreground/40"
              >
                <IFluentCalendarLtr24Regular class="size-10" />
              </div>
              <span
                v-if="otherEventTag(otherEvent)"
                class="absolute left-0 top-0 border-r border-b border-border bg-background/90 px-2.5 py-1 font-mono text-[0.625rem] font-bold uppercase tracking-wider text-brand"
              >
                {{ otherEventTag(otherEvent) }}
              </span>
            </div>
            <span class="mt-4 font-mono text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">
              {{ otherEventDate(otherEvent) }}
            </span>
            <h3 class="mt-2 text-pretty text-lg font-bold leading-snug text-foreground transition-colors group-hover:text-brand">
              {{ otherEventTitle(otherEvent) }}
            </h3>
          </Link>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

import Button from '@/Components/ui/button/Button.vue';
import EventActions from '@/Components/Calendar/EventActions.vue';
import EventDetailsCard from '@/Components/Calendar/EventDetailsCard.vue';
import EventHero from '@/Components/Calendar/EventHero.vue';
import EventImageGallery from '@/Components/Calendar/EventImageGallery.vue';
import PublicAgendaList from '@/Components/Public/PublicAgendaList.vue';
import PublicMeetingDocuments, { type PublicMeetingDocument } from '@/Components/Public/PublicMeetingDocuments.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { useEventStatus } from '@/Composables/useEventStatus';
import { useShareLink } from '@/Composables/useShareLink';
import { formatStaticTime } from '@/Utils/IntlTime';
import { getCalendarEvent2Route } from '@/Utils/Route';
import { LocaleEnum } from '@/Types/enums';
import IFluentArrowLeft20Regular from '~icons/fluent/arrow-left-20-regular';
import IFluentArrowRight20Regular from '~icons/fluent/arrow-right-20-regular';
import IFluentCalendarLtr24Regular from '~icons/fluent/calendar-ltr-24-regular';
import IFluentShare20Regular from '~icons/fluent/share-20-regular';

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
const locale = computed(() => (page.props.app.locale ?? LocaleEnum.LT) as LocaleEnum);

const eventTitle = computed(() =>
  Array.isArray(props.event.title) ? props.event.title.join(' ') : String(props.event.title ?? ''),
);

// Set up breadcrumbs in band placement
usePageBreadcrumbs(
  () =>
    BreadcrumbHelpers.publicContent([
      BreadcrumbHelpers.createRouteBreadcrumb(
        'Kalendorius',
        'calendar.list',
        { lang: locale.value },
        IFluentCalendarLtr24Regular,
      ),
      BreadcrumbHelpers.createBreadcrumbItem(eventTitle.value),
    ]),
  { placement: 'band' },
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

/** Share link logic */
const { share } = useShareLink();
const handleShare = () => share({ title: eventTitle.value });

/** The server already hands these over soonest-first; take top 3 for editorial grid. */
const otherEvents = computed(() =>
  props.calendar.filter(other => other.id !== props.event.id).slice(0, 3),
);

const otherEventTitle = (ev: App.Entities.Calendar): string =>
  Array.isArray(ev.title) ? ev.title.join(' ') : String(ev.title ?? '');

const otherEventImage = (ev: App.Entities.Calendar & { images?: Array<{ original_url: string }> }): string | null => {
  if (!ev.images) return null;
  if (Array.isArray(ev.images) && ev.images[0]?.original_url) return ev.images[0].original_url;
  return null;
};

const otherEventTag = (ev: App.Entities.Calendar): string | null =>
  ev.category?.name ?? ev.tenant?.shortname ?? null;

const otherEventDate = (ev: App.Entities.Calendar): string =>
  formatStaticTime(new Date(ev.date), { weekday: 'short', month: 'short', day: 'numeric' }, locale.value as LocaleEnum);

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
