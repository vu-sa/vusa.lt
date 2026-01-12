<template>
  <!-- Desktop Hero (lg+) - Full overlay design -->
  <header class="hidden lg:block relative overflow-hidden bg-gradient-to-br from-zinc-900 to-zinc-800 text-white"
    :class="heroContainerClasses" :style="backgroundStyle">
    <!-- Background Image Overlay - stronger gradient for text readability -->
    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-black/30" />

    <!-- Decorative Pattern for No Image State -->
    <div v-if="!hasImage" class="absolute inset-0 opacity-10">
      <svg class="h-full w-full" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <pattern id="calendar-pattern-desktop" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
            <circle cx="10" cy="10" r="1.5" fill="currentColor" opacity="0.3" />
            <rect x="5" y="5" width="10" height="10" fill="none" stroke="currentColor" opacity="0.2"
              stroke-width="0.5" />
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#calendar-pattern-desktop)" />
      </svg>
    </div>

    <!-- Breadcrumb Navigation -->
    <nav class="relative z-20 bg-black/20 backdrop-blur-sm">
      <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 text-sm font-medium text-white/80">
          <Link :href="route('home', { lang: locale, subdomain: 'www' })" class="hover:text-red-300 transition-colors">
          {{ $t("Pradžia") }}
          </Link>
          <IFluentChevronRight16Regular class="text-white/60" />
          <Link :href="route('calendar.list', { lang: locale })" class="hover:text-red-300 transition-colors">
          {{ $t("Kalendorius") }}
          </Link>
          <IFluentChevronRight16Regular class="text-white/60" />
          <span class="text-white/90 truncate">{{ event.title }}</span>
        </div>
      </div>
    </nav>

    <!-- Hero Content -->
    <div class="absolute inset-0 flex items-end">
      <div class="relative z-10 w-full">
        <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
          <div class="max-w-4xl space-y-4">
            <!-- Event Title -->
            <h1 class="text-4xl font-extrabold leading-tight text-white lg:text-5xl xl:text-6xl drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
              {{ event.title }}
            </h1>

            <!-- Event Status Badge -->
            <div v-if="eventStatus">
              <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-medium backdrop-blur-sm"
                :class="statusBadgeClasses">
                <Icon :icon="statusIcon" class="h-4 w-4" />
                {{ eventStatus }}
              </span>
            </div>

            <!-- Event Metadata -->
            <div class="flex flex-wrap gap-6 text-white drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
              <div class="flex items-center gap-2 min-w-0">
                <IFluentCalendarLtr20Regular class="flex-shrink-0 text-red-300" />
                <span class="font-medium">{{ formattedDateTime }}</span>
              </div>
              <div v-if="event.location" class="flex items-center gap-2 min-w-0">
                <IFluentLocation20Regular class="flex-shrink-0 text-red-300" />
                <span class="truncate">{{ event.location }}</span>
              </div>
              <div class="flex items-center gap-2 min-w-0">
                <IFluentPeopleTeam20Regular class="flex-shrink-0 text-red-300" />
                <span class="truncate">{{ event.organizer || event.tenant?.shortname }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Mobile Hero (< lg) - Stacked design with image on top -->
  <div class="lg:hidden">
    <!-- Breadcrumb Navigation -->
    <nav class="bg-zinc-100 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
      <div class="px-4 py-3">
        <div class="flex items-center gap-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">
          <Link :href="route('home', { lang: locale, subdomain: 'www' })" class="hover:text-vusa-red transition-colors">
            {{ $t("Pradžia") }}
          </Link>
          <IFluentChevronRight16Regular class="text-zinc-400" />
          <Link :href="route('calendar.list', { lang: locale })" class="hover:text-vusa-red transition-colors">
            {{ $t("Kalendorius") }}
          </Link>
        </div>
      </div>
    </nav>

    <!-- Image (if available) -->
    <div v-if="hasImage" class="relative aspect-video bg-zinc-200 dark:bg-zinc-800">
      <img 
        :src="heroImageUrl" 
        :alt="String(event.title)"
        class="w-full h-full object-cover"
      >
    </div>
    
    <!-- Decorative header for no image -->
    <div v-else class="relative h-32 bg-gradient-to-br from-vusa-red to-rose-600">
      <div class="absolute inset-0 opacity-20">
        <svg class="h-full w-full" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern id="calendar-pattern-mobile" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
              <circle cx="10" cy="10" r="1.5" fill="white" opacity="0.3" />
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#calendar-pattern-mobile)" />
        </svg>
      </div>
    </div>

    <!-- Content below image -->
    <div class="px-4 py-6 bg-white dark:bg-zinc-900">
      <!-- Event Status Badge -->
      <div v-if="eventStatus" class="mb-3">
        <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-medium"
          :class="mobileStatusBadgeClasses">
          <Icon :icon="statusIcon" class="h-4 w-4" />
          {{ eventStatus }}
        </span>
      </div>

      <!-- Event Title -->
      <h1 class="text-2xl sm:text-3xl font-extrabold leading-tight text-zinc-900 dark:text-zinc-100 mb-4">
        {{ event.title }}
      </h1>

      <!-- Event Metadata -->
      <div class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
        <div class="flex items-center gap-2">
          <IFluentCalendarLtr20Regular class="flex-shrink-0 text-vusa-red w-5 h-5" />
          <span class="font-medium">{{ formattedDateTime }}</span>
        </div>
        <div v-if="event.location" class="flex items-center gap-2">
          <IFluentLocation20Regular class="flex-shrink-0 text-vusa-red w-5 h-5" />
          <span>{{ event.location }}</span>
        </div>
        <div class="flex items-center gap-2">
          <IFluentPeopleTeam20Regular class="flex-shrink-0 text-vusa-red w-5 h-5" />
          <span>{{ event.organizer || event.tenant?.shortname }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { Link, usePage } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'

import { formatDateRange } from '@/Utils/IntlTime'

interface Props {
  event: App.Entities.Calendar
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => page.props.app.locale)

// Get hero image URL - uses main_image_url accessor with fallback
const heroImageUrl = computed(() => {
  return (props.event as any).main_image_url || null
})

// Check if event has hero image
const hasImage = computed(() => !!heroImageUrl.value)

// Background style computation (for desktop hero)
const backgroundStyle = computed(() => {
  if (!hasImage.value || !heroImageUrl.value) {
    return {}
  }

  return {
    backgroundImage: `url(${heroImageUrl.value})`,
    backgroundSize: 'cover',
    backgroundPosition: 'center',
    backgroundRepeat: 'no-repeat',
  }
})

// Responsive height classes with proper aspect ratio
const heroContainerClasses = computed(() => {
  // Use aspect ratio classes for better consistency
  if (hasImage.value) {
    return 'aspect-[16/9] min-h-[400px] max-h-[600px] lg:aspect-[21/9] lg:min-h-[500px]'
  } else {
    return 'aspect-[16/9] min-h-[400px] max-h-[500px] lg:aspect-[21/9]'
  }
})

// Format event date and time
const formattedDateTime = computed(() => {
  const startDate = new Date(props.event.date)
  const endDate = props.event.end_date ? new Date(props.event.end_date) : null

  return formatDateRange(startDate, endDate || undefined, locale.value as any)
})

// Event status computation
const eventStatus = computed(() => {
  const now = new Date()
  const eventDate = new Date(props.event.date)
  const endDate = props.event.end_date ? new Date(props.event.end_date) : eventDate

  if (endDate < now) {
    return $t('Renginys įvyko')
  } else if (eventDate <= now && endDate >= now) {
    return $t('Renginys vyksta')
  } else if (eventDate > now) {
    const timeDiff = eventDate.getTime() - now.getTime()
    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24))

    if (daysDiff === 0) {
      return $t('Renginys šiandien')
    } else if (daysDiff === 1) {
      return $t('Renginys rytoj')
    } else if (daysDiff <= 7) {
      return $t('Renginys šią savaitę')
    }
  }

  return null
})

// Status badge classes
const statusBadgeClasses = computed(() => {
  const now = new Date()
  const eventDate = new Date(props.event.date)
  const endDate = props.event.end_date ? new Date(props.event.end_date) : eventDate

  if (endDate < now) {
    return 'bg-zinc-500/80 text-zinc-100'
  } else if (eventDate <= now && endDate >= now) {
    return 'bg-green-500/80 text-white'
  } else {
    return 'bg-red-500/80 text-white'
  }
})

// Mobile status badge classes (solid colors, no backdrop blur)
const mobileStatusBadgeClasses = computed(() => {
  const now = new Date()
  const eventDate = new Date(props.event.date)
  const endDate = props.event.end_date ? new Date(props.event.end_date) : eventDate

  if (endDate < now) {
    return 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300'
  } else if (eventDate <= now && endDate >= now) {
    return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
  } else {
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  }
})

// Status icon component
const statusIcon = computed(() => {
  const now = new Date()
  const eventDate = new Date(props.event.date)
  const endDate = props.event.end_date ? new Date(props.event.end_date) : eventDate

  if (endDate < now) {
    return 'fluent:checkmark-circle-20-regular'
  } else if (eventDate <= now && endDate >= now) {
    return 'fluent:play-20-filled'
  } else {
    return 'fluent:person-add-20-regular'
  }
})
</script>

<style scoped>
/* Ensure proper contrast for text over images */
.drop-shadow-2xl {
  filter: drop-shadow(0 25px 25px rgb(0 0 0 / 0.15)) drop-shadow(0 10px 10px rgb(0 0 0 / 0.1)) drop-shadow(0 4px 6px rgb(0 0 0 / 0.1));
}

/* Smooth transitions for interactive elements */
.transition-colors {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Backdrop blur support */
.backdrop-blur-sm {
  backdrop-filter: blur(4px);
}

/* Ensure hero takes full width without container padding */
header {
  width: 100vw;
  margin-left: calc(-50vw + 50%);
}
</style>
