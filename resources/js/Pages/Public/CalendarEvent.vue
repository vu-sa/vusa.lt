<template>
  <div class="wrapper-wide">
    <!-- Main Content -->
    <div class="py-6 lg:py-10">
      <!-- Event Header Card -->
      <article class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm ring-1 ring-zinc-900/5 dark:ring-white/10 overflow-hidden">
        <!-- Header Section with Optional Image -->
        <header class="relative">
          <!-- Two-column header on desktop, stacked on mobile -->
          <div class="flex flex-col lg:flex-row">
            <!-- Image Section (accent, not full-bleed) -->
            <div v-if="hasImages" class="lg:w-2/5 relative">
              <div class="aspect-[4/3] lg:aspect-auto lg:h-full">
                <img 
                  :src="(normalizedImages[0] as any).original_url"
                  :alt="String(event.title)"
                  class="w-full h-full object-cover"
                >
              </div>
              <!-- Category badge on image -->
              <div 
                v-if="event.category"
                class="absolute top-3 left-3 px-2.5 py-1 text-xs font-semibold rounded-full bg-white/95 dark:bg-zinc-900/95 text-zinc-700 dark:text-zinc-300 backdrop-blur-sm shadow-sm"
              >
                {{ event.category.name }}
              </div>
            </div>

            <!-- Content Section -->
            <div class="flex-1 p-5 sm:p-6 lg:p-8" :class="{ 'lg:w-3/5': hasImages }">
              <!-- Status Badge -->
              <div v-if="eventStatus" class="mb-3">
                <span 
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full"
                  :class="statusBadgeClasses"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClasses" />
                  {{ eventStatus }}
                </span>
              </div>

              <!-- Title -->
              <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-zinc-900 dark:text-zinc-100 leading-tight mb-4">
                {{ event.title }}
              </h1>

              <!-- Primary Metadata (non-redundant, single source of truth) -->
              <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-zinc-600 dark:text-zinc-400 mb-5">
                <!-- Date & Time -->
                <div class="flex items-center gap-2">
                  <IFluentCalendarLtr16Regular class="w-4 h-4 text-vusa-red" />
                  <span class="font-medium">{{ formattedDate }}</span>
                </div>
                
                <!-- Time -->
                <div class="flex items-center gap-2">
                  <IFluentClock16Regular class="w-4 h-4 text-vusa-red" />
                  <span>{{ formattedTime }}</span>
                </div>

                <!-- Location -->
                <a 
                  v-if="event.location" 
                  :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(String(event.location))}`"
                  target="_blank"
                  class="flex items-center gap-2 hover:text-vusa-red transition-colors"
                >
                  <IFluentLocation16Regular class="w-4 h-4 text-vusa-red" />
                  <span class="underline-offset-2 hover:underline">{{ event.location }}</span>
                </a>
              </div>

              <!-- Organizer Info -->
              <div class="flex items-center gap-3 text-sm text-zinc-600 dark:text-zinc-400 pb-5 border-b border-zinc-200/60 dark:border-zinc-700/40">
                <div v-if="event.organizer || event.tenant" class="flex items-center gap-2">
                  <IFluentPeopleTeam16Regular class="w-4 h-4 text-vusa-red" />
                  <span>{{ event.organizer || event.tenant?.shortname }}</span>
                </div>
                <div v-if="event.tenant && event.organizer" class="flex items-center gap-2">
                  <span class="text-zinc-300 dark:text-zinc-700">•</span>
                  <span>{{ event.tenant.shortname }}</span>
                </div>
              </div>

              <!-- Action Buttons (inline, not separate bar) -->
              <div class="flex flex-wrap gap-2 pt-5">
                <!-- Primary CTA -->
                <Button 
                  v-if="(event as any).url" 
                  size="default"
                  class="gap-2 font-semibold"
                  :class="primaryButtonClasses"
                  as="a"
                  :href="(event as any).url" 
                  target="_blank"
                >
                  <component :is="primaryIcon" class="w-4 h-4" />
                  {{ primaryActionText }}
                </Button>

                <!-- Google Calendar -->
                <Button 
                  v-if="googleLink" 
                  variant="outline" 
                  size="default"
                  class="gap-2"
                  as="a" 
                  :href="googleLink" 
                  target="_blank" 
                  rel="noopener noreferrer"
                >
                  <IMdiGoogle class="w-4 h-4" />
                  <span class="hidden sm:inline">{{ $t('Kalendorius') }}</span>
                </Button>

                <!-- Facebook Event -->
                <Button 
                  v-if="event.facebook_url" 
                  variant="outline" 
                  size="default"
                  class="gap-2"
                  as="a" 
                  :href="event.facebook_url" 
                  target="_blank" 
                  rel="noopener noreferrer"
                >
                  <IMdiFacebook class="w-4 h-4" />
                  <span class="hidden sm:inline">Facebook</span>
                </Button>

                <!-- Share Button -->
                <Button 
                  variant="outline" 
                  size="default"
                  class="gap-2"
                  @click="handleShare"
                >
                  <IFluentShare16Regular class="w-4 h-4" />
                  <span class="hidden sm:inline">{{ $t('Dalinkis') }}</span>
                </Button>
              </div>
            </div>
          </div>
        </header>

        <!-- Content Body -->
        <div class="border-t border-zinc-200/60 dark:border-zinc-700/40">
          <div class="grid lg:grid-cols-3 gap-0">
            <!-- Main Content Area -->
            <main class="lg:col-span-2 p-6 sm:p-8 lg:p-10 lg:border-r border-zinc-200/60 dark:border-zinc-700/40">
              <!-- Description -->
              <section class="prose prose-zinc max-w-none dark:prose-invert prose-headings:font-bold prose-p:text-zinc-600 dark:prose-p:text-zinc-400 prose-a:text-vusa-red">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-3 mb-4 !mt-0">
                  <div class="w-1 h-6 bg-vusa-red rounded-full" />
                  {{ $t("Apie renginį") }}
                </h2>
                <div v-html="event.description" />
              </section>

              <!-- Video Section -->
              <section v-if="event.video_url" class="mt-10 pt-10 border-t border-zinc-200/60 dark:border-zinc-700/40">
                <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-3 mb-5">
                  <div class="w-1 h-5 bg-vusa-red rounded-full" />
                  {{ $t("Video") }}
                </h3>
                <div class="overflow-hidden rounded-xl ring-1 ring-zinc-200/80 dark:ring-zinc-700/50">
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

              <!-- Image Gallery (if more than 1 image) -->
              <section v-if="hasImages && normalizedImages.length > 1" class="mt-10 pt-10 border-t border-zinc-200/60 dark:border-zinc-700/40">
                <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-3 mb-5">
                  <div class="w-1 h-5 bg-vusa-red rounded-full" />
                  {{ $t("Nuotraukos") }} ({{ normalizedImages.length }})
                </h3>
                <EventImageGallery :images="normalizedImages" :event-title="String(event.title)" />
              </section>
            </main>

            <!-- Sidebar (Desktop only) -->
            <aside class="hidden lg:block lg:sticky lg:top-8 lg:self-start p-6 space-y-8">
              <!-- Quick Share Card -->
              <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl">
                <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3 flex items-center gap-2">
                  <IFluentShare16Regular class="w-4 h-4 text-vusa-red" />
                  {{ $t("Dalinkis renginiu") }}
                </h4>
                <div class="flex gap-2">
                  <Button 
                    v-if="googleLink" 
                    variant="outline" 
                    size="sm"
                    class="flex-1 gap-1.5 text-xs"
                    as="a" 
                    :href="googleLink" 
                    target="_blank"
                  >
                    <IMdiGoogle class="w-3.5 h-3.5" />
                    Google
                  </Button>
                  <Button 
                    v-if="event.facebook_url" 
                    variant="outline" 
                    size="sm"
                    class="flex-1 gap-1.5 text-xs"
                    as="a" 
                    :href="event.facebook_url" 
                    target="_blank"
                  >
                    <IMdiFacebook class="w-3.5 h-3.5" />
                    Facebook
                  </Button>
                  <Button 
                    variant="outline" 
                    size="sm"
                    class="gap-1.5 text-xs"
                    @click="handleShare"
                  >
                    <IFluentShare16Regular class="w-3.5 h-3.5" />
                  </Button>
                </div>
              </div>

              <!-- Upcoming Events (Desktop only - no duplicate) -->
              <UpcomingEventsCompact 
                :events="calendar" 
                :locale="$page.props.app.locale"
                :exclude-event-id="event.id"
                :max-visible="4"
              />
            </aside>
          </div>
        </div>
      </article>

      <!-- Mobile Upcoming Events (below the card, separate from sticky bar) -->
      <section v-if="mobileUpcomingEvents.length > 0" class="lg:hidden mt-8 mb-20">
        <div class="bg-white dark:bg-zinc-900 rounded-xl ring-1 ring-zinc-900/5 dark:ring-white/10 p-5 shadow-sm">
          <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2 mb-4">
            <div class="w-1 h-5 bg-vusa-red rounded-full" />
            {{ $t("Artėjantys renginiai") }}
          </h3>
          <div class="space-y-2">
            <Link 
              v-for="upcomingEvent in mobileUpcomingEvents" 
              :key="upcomingEvent.id"
              :href="route('calendar.event', { calendar: upcomingEvent.id, lang: $page.props.app.locale })"
              class="flex items-center gap-3 p-2.5 -mx-1 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group"
            >
              <!-- Compact Date Badge -->
              <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-vusa-red text-white flex flex-col items-center justify-center text-center">
                <span class="text-[10px] font-medium uppercase leading-tight">{{ formatMonth(upcomingEvent.date) }}</span>
                <span class="text-sm font-bold leading-tight">{{ formatDay(upcomingEvent.date) }}</span>
              </div>
              <!-- Event Info -->
              <div class="flex-1 min-w-0">
                <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100 group-hover:text-vusa-red transition-colors line-clamp-1">
                  {{ upcomingEvent.title }}
                </h4>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                  {{ formatEventTime(upcomingEvent.date) }}
                </p>
              </div>
              <IFluentChevronRight16Regular class="flex-shrink-0 text-zinc-400 group-hover:text-vusa-red transition-colors" />
            </Link>
          </div>
        </div>
      </section>
    </div>

    <!-- Mobile Sticky Action Bar -->
    <div 
      v-if="(event as any).url || googleLink || event.facebook_url" 
      class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/98 dark:bg-zinc-900/98 backdrop-blur-md border-t border-zinc-200/80 dark:border-zinc-700/60 p-4 pb-safe shadow-[0_-4px_20px_-4px_rgba(0,0,0,0.1)] dark:shadow-[0_-4px_20px_-4px_rgba(0,0,0,0.4)]"
    >
      <div class="flex gap-2">
        <!-- Primary CTA -->
        <Button 
          v-if="(event as any).url" 
          size="default"
          class="flex-1 gap-2 font-semibold"
          :class="primaryButtonClasses"
          as="a"
          :href="(event as any).url" 
          target="_blank"
        >
          <component :is="primaryIcon" class="w-4 h-4" />
          {{ primaryActionText }}
        </Button>
        
        <!-- Secondary actions -->
        <Button 
          v-if="googleLink" 
          variant="outline" 
          size="default"
          as="a" 
          :href="googleLink" 
          target="_blank"
        >
          <IMdiGoogle class="w-4 h-4" />
        </Button>
        
        <Button 
          variant="outline" 
          size="default"
          @click="handleShare"
        >
          <IFluentShare16Regular class="w-4 h-4" />
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import { format } from "date-fns";
import { lt, enUS } from "date-fns/locale";

import PublicBreadcrumbs from "@/Components/Public/PublicBreadcrumbs.vue";
import UpcomingEventsCompact from "@/Components/Calendar/UpcomingEventsCompact.vue";
import EventImageGallery from "@/Components/Calendar/EventImageGallery.vue";
import Button from "@/Components/ui/button/Button.vue";
import { usePageBreadcrumbs, BreadcrumbHelpers } from "@/Composables/useBreadcrumbsUnified";

const props = defineProps<{
  event: App.Entities.Calendar;
  calendar: App.Entities.Calendar[];
  googleLink: string;
}>();

const page = usePage()
const locale = computed(() => page.props.app.locale)
const dateLocale = computed(() => locale.value === 'lt' ? lt : enUS)

// Set up breadcrumbs using the recommended API with proper lifecycle management
usePageBreadcrumbs(() => {
  const eventTitle = Array.isArray(props.event.title) 
    ? props.event.title.join(' ') 
    : (props.event.title || '');
  
  return BreadcrumbHelpers.publicContent([
    { label: 'Kalendorius', href: route('calendar.list', { lang: locale.value }) },
    { label: eventTitle },
  ]);
});

// Event status helpers
const isEventPast = computed(() => {
  const now = new Date()
  const eventEndDate = props.event.end_date ? new Date(props.event.end_date) : new Date(props.event.date)
  return eventEndDate < now
})

const isEventActive = computed(() => {
  const now = new Date()
  const eventStartDate = new Date(props.event.date)
  const eventEndDate = props.event.end_date ? new Date(props.event.end_date) : eventStartDate
  return eventStartDate <= now && eventEndDate >= now
})

const eventStatus = computed(() => {
  if (isEventPast.value) return $t('Renginys įvyko')
  if (isEventActive.value) return $t('Vyksta dabar')
  return $t('Renginys įvyks')
})

const statusBadgeClasses = computed(() => {
  if (isEventPast.value) return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
  if (isEventActive.value) return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
  return 'bg-vusa-red/10 text-vusa-red dark:bg-vusa-red/20'
})

const statusDotClasses = computed(() => {
  if (isEventPast.value) return 'bg-zinc-400'
  if (isEventActive.value) return 'bg-green-500 animate-pulse'
  return 'bg-vusa-red'
})

// Primary action helpers
const primaryActionText = computed(() => {
  if (isEventPast.value) return $t('Renginys įvyko')
  if (isEventActive.value) return $t('Dalyvauk dabar')
  return $t('Dalyvauk')
})

const primaryIcon = computed(() => {
  if (isEventPast.value) return 'IFluentCheckmarkCircle16Regular'
  if (isEventActive.value) return 'IFluentPlay16Filled'
  return 'IFluentPersonAdd16Regular'
})

const primaryButtonClasses = computed(() => {
  if (isEventPast.value) return 'opacity-60 cursor-not-allowed pointer-events-none bg-zinc-400 hover:bg-zinc-400'
  if (isEventActive.value) return 'bg-green-600 hover:bg-green-700 text-white'
  return ''
})

// Formatted date and time
const formattedDate = computed(() => {
  const startDate = new Date(props.event.date)
  const endDate = props.event.end_date ? new Date(props.event.end_date) : null
  
  if (endDate && format(startDate, 'yyyy-MM-dd') !== format(endDate, 'yyyy-MM-dd')) {
    // Multi-day event
    return `${format(startDate, 'MMM d', { locale: dateLocale.value })} – ${format(endDate, 'MMM d, yyyy', { locale: dateLocale.value })}`
  }
  
  return format(startDate, 'EEEE, MMMM d, yyyy', { locale: dateLocale.value })
})

const formattedTime = computed(() => {
  const startTime = format(new Date(props.event.date), 'HH:mm', { locale: dateLocale.value })
  if (props.event.end_date) {
    const endTime = format(new Date(props.event.end_date), 'HH:mm', { locale: dateLocale.value })
    return `${startTime} – ${endTime}`
  }
  return startTime
})

// Check if event has images and normalize them
const hasImages = computed(() => {
  const images = (props.event as any).images;
  if (!images) return false;
  if (Array.isArray(images)) return images.length > 0;
  if (typeof images === 'object') return Object.keys(images).length > 0;
  return false;
});

// Normalize images to array format for EventImageGallery
const normalizedImages = computed(() => {
  const images = (props.event as any).images;
  if (!images) return [];
  if (Array.isArray(images)) return images;
  if (typeof images === 'object') return Object.values(images);
  return [];
});

// Get upcoming events (excluding current event)
const upcomingEvents = computed(() => {
  if (!props.calendar) return [];

  const now = new Date();
  return props.calendar
    .filter(e => e.id !== props.event.id && new Date(e.date) >= now)
    .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
    .slice(0, 4);
});

// Mobile upcoming events (for the inline list without card wrapper)
const mobileUpcomingEvents = computed(() => {
  if (!props.calendar) return [];
  
  const now = new Date();
  return props.calendar
    .filter(e => e.id !== props.event.id && new Date(e.date) >= now)
    .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
    .slice(0, 5);
});

// Format helpers for mobile upcoming events
const formatMonth = (dateStr: string) => {
  return format(new Date(dateStr), 'MMM', { locale: dateLocale.value });
};

const formatDay = (dateStr: string) => {
  return format(new Date(dateStr), 'd', { locale: dateLocale.value });
};

const formatEventTime = (dateStr: string) => {
  return format(new Date(dateStr), 'HH:mm', { locale: dateLocale.value });
};

// Share handler with native share API fallback
const handleShare = async () => {
  const eventTitle = Array.isArray(props.event.title) ? props.event.title.join(' ') : (props.event.title || '')
  const shareData = {
    title: eventTitle,
    text: `${eventTitle} - ${formattedDate.value}`,
    url: window.location.href
  }

  if (typeof navigator !== 'undefined' && 'share' in navigator) {
    try {
      await navigator.share(shareData)
    } catch (error) {
      // User cancelled or share failed, fallback to clipboard
      await copyToClipboard()
    }
  } else {
    await copyToClipboard()
  }
}

const copyToClipboard = async () => {
  try {
    await navigator.clipboard.writeText(window.location.href)
    // Could add a toast notification here
  } catch (error) {
    console.error('Failed to copy to clipboard:', error)
  }
}
</script>

<style scoped>
/* Mobile safe area for devices with notches/home indicators */
.pb-safe {
  padding-bottom: max(0.75rem, env(safe-area-inset-bottom));
}

/* Respect reduced motion preferences */
@media (prefers-reduced-motion: reduce) {
  .animate-pulse {
    animation: none;
  }
}
</style>
