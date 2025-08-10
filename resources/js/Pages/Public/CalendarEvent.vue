<template>
  <div class="wrapper-content">
    <!-- Event Hero Section -->
    <EventHero :event class="full-bleed" />

    <!-- Action Bar with vertical separation -->
    <div class="py-8">
      <EventActions :event :google-link
        :detail-url="route('calendar.event', { calendar: event.id, lang: $page.props.app.locale })"
        variant="consolidated" />
    </div>

    <!-- Main Content -->
    <div class="container">
      <div class="grid gap-2 md:gap-6 lg:gap-12 lg:grid-cols-3">
        <!-- Primary Content -->
        <main class="space-y-8 lg:space-y-12 lg:col-span-2">
          <!-- Event Description -->
          <section class="prose prose-lg max-w-none dark:prose-invert" aria-labelledby="event-description-heading">
            <h2 id="event-description-heading"
              class="border-b border-zinc-200 pb-4 text-3xl font-bold text-zinc-900 dark:border-zinc-700 dark:text-zinc-100">
              {{ $t("Apie renginį") }}
            </h2>
            <div class="mt-8" v-html="event.description" />
          </section>

          <!-- Video Section -->
          <section v-if="event.video_url" class="relative">
            <!-- Decorative divider -->
            <div class="flex items-center justify-center py-8">
              <div
                class="flex-1 h-px bg-gradient-to-r from-transparent via-zinc-300 to-transparent dark:via-zinc-600" />
              <div class="px-4">
                <div class="w-2 h-2 bg-vusa-red rounded-full" />
              </div>
              <div
                class="flex-1 h-px bg-gradient-to-r from-transparent via-zinc-300 to-transparent dark:via-zinc-600" />
            </div>

            <h3 class="mb-6 text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-3">
              <div class="w-1 h-8 bg-vusa-red rounded-full" />
              {{ $t("Video") }}
            </h3>
            <div class="overflow-hidden rounded-xl shadow-lg ring-1 ring-zinc-200 dark:ring-zinc-700">
              <iframe class="aspect-video h-auto w-full" width="560" height="315"
                :src="`https://www.youtube-nocookie.com/embed/${event.video_url}`" title="YouTube video player"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen />
            </div>
          </section>

          <!-- Enhanced Image Gallery -->
          <section v-if="(event as any).images && (event as any).images.length > 0" class="relative">
            <!-- Decorative divider -->
            <div class="flex items-center justify-center py-8">
              <div
                class="flex-1 h-px bg-gradient-to-r from-transparent via-zinc-300 to-transparent dark:via-zinc-600" />
              <div class="px-4">
                <div class="w-2 h-2 bg-vusa-red rounded-full" />
              </div>
              <div
                class="flex-1 h-px bg-gradient-to-r from-transparent via-zinc-300 to-transparent dark:via-zinc-600" />
            </div>

            <EventImageGallery :images="(event as any).images" :event-title="String(event.title)" />
          </section>

          <!-- Related Events -->
        </main>

        <!-- Mobile Sidebar Content (Shows on mobile, hidden on desktop) -->
        <div class="block lg:hidden space-y-6 mt-8">
          <!-- Event Metadata -->
          <div
            class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="bg-zinc-50 dark:bg-zinc-800/50 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
              <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <IFluentInfo20Regular class="w-5 h-5 text-vusa-red dark:text-vusa-red" />
                {{ $t("Renginio informacija") }}
              </h3>
            </div>
            <div class="p-6">
              <CalendarEventMeta :date="event.date" :end-date="event.end_date" :location="String(event.location || '')"
                :organizer="String(event.organizer || '')" :tenant="event.tenant" enable-location-link
                class="space-y-3" />
            </div>
          </div>

          <!-- Calendar Widget -->
          <div class="space-y-4">
            <EventCalendar class="mx-auto w-fit" :calendar-events="calendar" :locale="$page.props.app.locale" />
          </div>
        </div>

        <!-- Desktop Sidebar -->
        <aside class="hidden space-y-6 lg:block">
          <!-- Event Metadata -->
          <div
            class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="bg-zinc-50 dark:bg-zinc-800/50 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
              <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <IFluentInfo20Regular class="w-5 h-5 text-vusa-red dark:text-vusa-red" />
                {{ $t("Renginio informacija") }}
              </h3>
            </div>
            <div class="p-4">
              <CalendarEventMeta :date="event.date" :end-date="event.end_date" :location="String(event.location || '')"
                :organizer="String(event.organizer || '')" :tenant="event.tenant" enable-location-link
                class="space-y-2" />
            </div>
          </div>

          <!-- Calendar Widget -->
          <div class="space-y-4">
            <EventCalendar class="mx-auto w-fit" :calendar-events="calendar" :locale="$page.props.app.locale" />
          </div>
        </aside>
      </div>
      <section v-if="upcomingEvents.length > 0" class="relative">
        <!-- Decorative divider -->
        <div class="flex items-center justify-center py-8">
          <div class="flex-1 h-px bg-gradient-to-r from-transparent via-zinc-300 to-transparent dark:via-zinc-600" />
          <div class="px-4">
            <div class="w-2 h-2 bg-vusa-red rounded-full" />
          </div>
          <div class="flex-1 h-px bg-gradient-to-r from-transparent via-zinc-300 to-transparent dark:via-zinc-600" />
        </div>

        <div class="flex items-center gap-3 mb-6">
          <div class="w-1 h-8 bg-vusa-red rounded-full" />
          <div>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
              {{ $t("Daugiau renginių") }}
            </h2>
          </div>
        </div>
        <div class="space-y-3">
          <EventCard v-for="upcomingEvent in upcomingEvents" :key="upcomingEvent.id" :event="upcomingEvent"
            variant="compact" :google-link="generateGoogleLink(upcomingEvent)" />
        </div>

        <!-- View all events link -->
        <div class="my-6 pt-6 border-t border-zinc-200 dark:border-zinc-700 text-center">
          <Button as="a" :href="route('calendar.list', { lang: $page.props.app.locale })" variant="outline"
            class="gap-2">
            <IFluentCalendarLtr20Regular class="w-4 h-4" />
            {{ $t('Žiūrėti visus renginius') }}
          </Button>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

import CalendarCard from "@/Components/Calendar/CalendarCard.vue";
import EventCalendar from "@/Components/Calendar/EventCalendar.vue";
import EventHero from "@/Components/Calendar/EventHero.vue";
import EventActions from "@/Components/Calendar/EventActions.vue";
import EventImageGallery from "@/Components/Calendar/EventImageGallery.vue";
import EventSocialShare from "@/Components/Calendar/EventSocialShare.vue";
import EventCard from "@/Components/Calendar/EventCard.vue";
import CalendarEventMeta from "@/Components/Calendar/CalendarEventMeta.vue";
import Button from "@/Components/ui/button/Button.vue";
import { formatStaticTime } from "@/Utils/IntlTime";

const props = defineProps<{
  event: App.Entities.Calendar;
  calendar: App.Entities.Calendar[];
  googleLink: string;
}>();

const page = usePage()

// Get upcoming events (excluding current event)
const upcomingEvents = computed(() => {
  if (!props.calendar) return [];

  const now = new Date();
  return props.calendar
    .filter(e => e.id !== props.event.id && new Date(e.date) >= now)
    .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
    .slice(0, 4);
});

// Generate Google Calendar link for events
const generateGoogleLink = (event: App.Entities.Calendar) => {
  if (!event) return '';

  const startDate = new Date(event.date);
  const endDate = event.end_date ? new Date(event.end_date) : new Date(startDate.getTime() + 60 * 60 * 1000); // Default 1 hour

  const formatGoogleDate = (date: Date) => {
    return date.toISOString().replace(/-|:|\.\d+/g, '');
  };

  const { description } = event
  const details = Array.isArray(description)
    ? description.join(' ').replace(/<[^>]*>/g, ' ')
    : (description || '').replace(/<[^>]*>/g, ' ');

  const title = Array.isArray(event.title) ? event.title.join(' ') : (event.title || '');
  const location = Array.isArray(event.location) ? event.location.join(' ') : (event.location || '');

  return `https://www.google.com/calendar/render?action=TEMPLATE` +
    `&text=${encodeURIComponent(title)}` +
    `&dates=${formatGoogleDate(startDate)}/${formatGoogleDate(endDate)}` +
    `&details=${encodeURIComponent(details)}${location ? `&location=${encodeURIComponent(location)}` : ''}`;
};
</script>

<style scoped>
/* Custom styles for the enhanced calendar event page */

/* Responsive content spacing */
@media (max-width: 1024px) {
  .pb-safe-bottom {
    padding-bottom: max(1rem, env(safe-area-inset-bottom));
  }
}

/* Smooth transitions for interactive elements */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

/* Enhanced section animations */
section {
  animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Staggered animations for sections */
section:nth-child(1) {
  animation-delay: 0.1s;
}

section:nth-child(2) {
  animation-delay: 0.2s;
}

section:nth-child(3) {
  animation-delay: 0.3s;
}

section:nth-child(4) {
  animation-delay: 0.4s;
}

section:nth-child(5) {
  animation-delay: 0.5s;
}

/* Enhanced decorative elements */
.bg-gradient-to-r {
  background-size: 200% 200%;
  animation: gradientShift 10s ease infinite;
}

@keyframes gradientShift {
  0% {
    background-position: 0% 50%;
  }

  50% {
    background-position: 100% 50%;
  }

  100% {
    background-position: 0% 50%;
  }
}

/* Reduce motion for users who prefer it */
@media (prefers-reduced-motion: reduce) {

  section,
  .group:hover,
  .bg-gradient-to-r {
    animation: none;
    transition: none;
    transform: none;
  }
}
</style>
