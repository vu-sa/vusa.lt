<template>
  <div class="event-calendar-section py-4 px-4 lg:px-0">
    <!-- Section header -->
    <div class="text-center mb-6 lg:mb-8">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="h-px w-12 bg-gradient-to-r from-transparent to-vusa-red/50" />
        <div class="w-3 h-3 rounded-full bg-vusa-red animate-pulse" />
        <div class="h-px w-12 bg-gradient-to-l from-transparent to-vusa-red/50" />
      </div>
      
      <h2 class="text-2xl lg:text-3xl font-bold text-zinc-900 dark:text-zinc-50 mb-3">
        {{ $page.props.app.locale === 'lt' 
          ? 'Sek visus VU studentų renginius bei įvykius!' 
          : 'Follow Vilnius University activities for students!' 
        }}
      </h2>
      
      <p class="text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
        {{ $page.props.app.locale === 'lt'
          ? 'Naršyk laiko juostą ir rask tau įdomius renginius'
          : 'Browse the timeline and find events that interest you'
        }}
      </p>
    </div>

    <!-- Calendar Sync Modal -->
    <CalendarSyncModal v-model:show-modal="showModal" @close="showModal = false" />

    <!-- Timeline content -->
    <!-- Loading state -->
    <FadeTransition>
      <div v-if="loading" class="w-full">
        <div class="hidden lg:block">
          <Skeleton class="w-full h-12 rounded-lg mb-4" />
          <Skeleton class="w-full h-48 rounded-xl" />
          <div class="grid grid-cols-2 gap-4 mt-6 max-w-2xl mx-auto">
            <Skeleton class="h-24 rounded-xl" />
            <Skeleton class="h-24 rounded-xl" />
          </div>
        </div>
        <div class="lg:hidden space-y-4">
          <Skeleton class="w-full h-20 rounded-xl" />
          <Skeleton class="w-full h-20 rounded-xl" />
          <Skeleton class="w-full h-20 rounded-xl" />
        </div>
      </div>

      <!-- Error state -->
      <div 
        v-else-if="error" 
        class="flex flex-col items-center justify-center py-12 text-center"
      >
        <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
          <IFluentWarning20Regular class="w-8 h-8 text-red-500 dark:text-red-400" />
        </div>
        <p class="text-red-600 dark:text-red-400 font-medium mb-2">
          {{ $t("Nepavyko užkrauti kalendoriaus įvykių") }}
        </p>
        <Button variant="outline" size="sm" @click="refresh">
          <IFluentArrowSync16Regular class="w-4 h-4 mr-1.5" />
          {{ $t("Bandyti dar kartą") }}
        </Button>
      </div>

      <!-- Timeline content (shown when not loading and no error) -->
      <div v-else>
        <!-- Desktop: Horizontal timeline -->
        <div class="hidden lg:block">
          <EventTimeline 
            :events="calendar" 
            :locale="$page.props.app.locale"
            :loading-past="loadingPast"
            :loading-future="loadingFuture"
            @open-sync-modal="showModal = true"
            @load-past="fetchPast"
            @load-future="fetchFuture"
          />
        </div>

        <!-- Mobile: Vertical timeline -->
        <div class="lg:hidden">
          <EventTimelineVertical 
            :events="calendar" 
            :locale="$page.props.app.locale"
            :loading-past="loadingPast"
            :loading-future="loadingFuture"
            @open-sync-modal="showModal = true"
            @load-past="fetchPast"
            @load-future="fetchFuture"
          />
        </div>
      </div>
    </FadeTransition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";

import { Button } from "@/Components/ui/button";
import CalendarSyncModal from "@/Components/Modals/CalendarSyncModal.vue";
import EventTimeline from "@/Components/Calendar/EventTimeline.vue";
import EventTimelineVertical from "@/Components/Calendar/EventTimelineVertical.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import Skeleton from "@/Components/ui/skeleton/Skeleton.vue";
import { useCalendarFetch } from "@/Services/ContentService";
import type { Calendar } from "@/Types/contentParts";

// Calendar event type
interface CalendarEvent {
  id: number;
  title: string;
  date: string;
  category: { id: number; name: string } | null;
  images: Array<{ url: string }>;
  [key: string]: unknown;
}

const props = defineProps<{
  element?: { json_content: Calendar['json_content']; options: Calendar['options'] };
  prefetchedCalendar?: CalendarEvent[];
}>();

const showModal = ref(false);

// Check if we have prefetched calendar data from server
const hasPrefetchedCalendar = computed(() => props.prefetchedCalendar && props.prefetchedCalendar.length > 0);

// Normalize allTenants to boolean (handles true, 1, "1", "true")
const allTenants = computed(() => {
  const val = props.element?.options?.allTenants;
  return val === true || val === 1 || val === '1' || val === 'true';
});

// Use the ContentService to fetch calendar data with date-based loading
// Only fetch from API if no prefetched data is available
const { 
  calendar: apiCalendar, 
  loading: apiLoading, 
  loadingPast,
  loadingFuture,
  error: apiError, 
  refresh,
  fetchPast,
  fetchFuture,
  initializeWithData,
} = useCalendarFetch({
  allTenants: allTenants.value,
  skipInitialFetch: hasPrefetchedCalendar.value,
});

// If we have prefetched data, initialize the calendar state with it
if (hasPrefetchedCalendar.value && props.prefetchedCalendar) {
  initializeWithData(props.prefetchedCalendar);
}

// Combine sources: prefer prefetched data for initial render, then use API data
// Note: Type cast maintains compatibility with EventTimeline props
const calendar = computed(() => apiCalendar.value ?? []);

// Loading state: only show loading when using API fetch and it's loading
const loading = computed(() => !hasPrefetchedCalendar.value && apiLoading.value);

// Error state: only show error when using API fetch and there's an error
const error = computed(() => !hasPrefetchedCalendar.value && apiError.value);
</script>
