<template>
  <div class="calendar-event-page min-h-screen bg-white dark:bg-zinc-900">
    <!-- Hero Section - Full Bleed -->
    <EventHero :event>
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
    <div class="wrapper">
      <div class="py-10 lg:py-16">
        <!-- Two Column Layout -->
        <div class="grid lg:grid-cols-12 gap-10 lg:gap-16">
          <!-- Main Content -->
          <main class="lg:col-span-8 space-y-12">
            <!-- Description leads the column: no heading needed -->
            <div
              v-if="event.description"
              class="typography max-w-none text-zinc-700 dark:text-zinc-300"
              v-html="event.description"
            />

            <!-- Video Section -->
            <section v-if="event.video_url">
              <div class="flex items-center gap-4 mb-6">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
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
          </main>

          <!-- Sidebar -->
          <aside class="lg:col-span-4 space-y-8 order-2 lg:order-none">
            <!-- top-28 clears the fixed main navigation (see MainNavigation.vue) -->
            <div class="lg:sticky lg:top-28 space-y-8">
              <EventDetailsCard
                :event
                :google-link
                :coordinates="eventLocation"
              />

              <UpcomingEventsCompact
                :events="calendar"
                :locale="locale"
                :exclude-event-id="event.id"
                :max-visible="3"
              />
            </div>
          </aside>
        </div>
      </div>
    </div>

    <!-- Mobile Sticky Action Bar - primary CTA only -->
    <div
      v-if="registrationUrl && !isPast"
      class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/98 dark:bg-zinc-900/98 backdrop-blur-md border-t border-zinc-200/80 dark:border-zinc-700/60 p-4 pb-safe shadow-[0_-4px_20px_-4px_rgba(0,0,0,0.1)] dark:shadow-[0_-4px_20px_-4px_rgba(0,0,0,0.4)]"
    >
      <EventActions
        variant="sticky"
        :registration-url="registrationUrl"
        :share-title="eventTitle"
        :is-past="isPast"
        :is-live="isLive"
      />
    </div>

    <!-- Bottom spacer for mobile sticky bar -->
    <div v-if="registrationUrl && !isPast" class="lg:hidden h-24" />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import EventActions from '@/Components/Calendar/EventActions.vue';
import EventDetailsCard from '@/Components/Calendar/EventDetailsCard.vue';
import EventHero from '@/Components/Calendar/EventHero.vue';
import EventImageGallery from '@/Components/Calendar/EventImageGallery.vue';
import UpcomingEventsCompact from '@/Components/Calendar/UpcomingEventsCompact.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { useEventStatus } from '@/Composables/useEventStatus';

const props = defineProps<{
  event: App.Entities.Calendar;
  calendar: App.Entities.Calendar[];
  googleLink: string;
  eventLocation: { lat: number; lng: number; display_name: string } | null;
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

const { isPast, isLive } = useEventStatus(() => props.event);

/** The call-to-action URL is stored as `cto_url`, not `url`. */
const registrationUrl = computed(() => (props.event.cto_url ? String(props.event.cto_url) : null));

// Normalize images to array format for EventImageGallery
const normalizedImages = computed(() => {
  const { images } = props.event as { images?: unknown };
  if (!images) return [];
  if (Array.isArray(images)) return images;
  if (typeof images === 'object') return Object.values(images);
  return [];
});
</script>

<style scoped>
/* Mobile safe area for devices with notches/home indicators */
.pb-safe {
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
}
</style>
