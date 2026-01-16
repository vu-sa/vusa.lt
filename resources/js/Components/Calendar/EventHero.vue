<template>
  <div class="event-hero-wrapper">
    <!-- Desktop Hero (lg+) - Immersive full-bleed design -->
    <header 
      class="hidden lg:block relative overflow-hidden"
      :class="heroContainerClasses"
    >
      <!-- Background Layer -->
      <div class="absolute inset-0">
        <!-- Image Background -->
        <div 
          v-if="hasImage" 
          class="absolute inset-0 bg-cover bg-center"
          :style="{ backgroundImage: `url(${heroImageUrl})` }"
        />
        
        <!-- Gradient Placeholder for No Image -->
        <div 
          v-else 
          class="absolute inset-0 bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900"
        >
          <!-- Animated mesh gradient overlay -->
          <div class="absolute inset-0 opacity-60 bg-[radial-gradient(ellipse_80%_50%_at_20%_40%,rgba(189,28,38,0.3),transparent),radial-gradient(ellipse_60%_40%_at_80%_60%,rgba(189,28,38,0.15),transparent)]" />
          
          <!-- Geometric pattern -->
          <svg class="absolute inset-0 w-full h-full opacity-[0.04]" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <pattern id="hero-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                <path d="M 60 0 L 0 0 0 60" fill="none" stroke="white" stroke-width="0.5"/>
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#hero-grid)" />
          </svg>
          
          <!-- Decorative accent shapes -->
          <div class="absolute top-1/4 -right-20 w-80 h-80 rounded-full bg-vusa-red/10 blur-3xl" />
          <div class="absolute -bottom-20 left-1/4 w-96 h-96 rounded-full bg-vusa-red/5 blur-3xl" />
        </div>
        
        <!-- Gradient overlay for text readability -->
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/70 to-black/40" />
      </div>

      <!-- Content Container - Absolute positioned at bottom -->
      <div class="absolute inset-x-0 bottom-0 z-10 pb-10 lg:pb-14 px-8">
        <div class="max-w-7xl mx-auto">
          <div class="max-w-4xl space-y-4">
              <!-- Category/Tenant badges -->
              <div class="flex items-center gap-3">
                <span 
                  v-if="event.tenant" 
                  class="px-3 py-1.5 text-xs font-medium rounded-full bg-vusa-red text-white"
                >
                  {{ event.tenant.shortname }}
                </span>
                <span 
                  v-if="event.category" 
                  class="px-3 py-1.5 text-xs font-semibold uppercase tracking-wider rounded-full bg-white/20 text-white backdrop-blur-md border border-white/20"
                >
                  {{ event.category.name }}
                </span>
              </div>

              <!-- Status Badge -->
              <div v-if="eventStatus">
                <span 
                  class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-full backdrop-blur-md"
                  :class="statusBadgeClasses"
                >
                  <span class="relative flex h-2.5 w-2.5">
                    <span 
                      v-if="isLive" 
                      class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                      :class="statusPingClasses"
                    />
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="statusDotClasses" />
                  </span>
                  {{ eventStatus }}
                </span>
              </div>

              <!-- Title -->
              <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-black leading-[1.05] text-white tracking-tight drop-shadow-[0_2px_4px_rgba(0,0,0,0.5)]">
                {{ event.title }}
              </h1>

              <!-- Metadata Row -->
              <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-white">
                <!-- Date -->
                <div class="flex items-center gap-2.5 text-base">
                  <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-white/20 backdrop-blur-md">
                    <IFluentCalendarLtr20Regular class="w-5 h-5 text-white" />
                  </div>
                  <span class="font-medium drop-shadow-sm">{{ formattedDateTime }}</span>
                </div>
                
                <!-- Location -->
                <a 
                  v-if="event.location" 
                  :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(String(event.location))}`"
                  target="_blank"
                  class="flex items-center gap-2.5 text-base group"
                >
                  <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-white/20 backdrop-blur-md group-hover:bg-white/30 transition-colors">
                    <IFluentLocation20Regular class="w-5 h-5 text-white" />
                  </div>
                  <span class="font-medium underline-offset-2 group-hover:underline drop-shadow-sm">{{ event.location }}</span>
                </a>
                
                <!-- Organizer -->
                <div v-if="event.organizer" class="flex items-center gap-2.5 text-base">
                  <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-white/20 backdrop-blur-md">
                    <IFluentPeopleTeam20Regular class="w-5 h-5 text-white" />
                  </div>
                  <span class="font-medium drop-shadow-sm">{{ event.organizer }}</span>
                </div>
              </div>

              <!-- Action Buttons Slot -->
              <div v-if="$slots.actions" class="pt-2">
                <slot name="actions" />
              </div>
            </div>
          </div>
        </div>
    </header>

    <!-- Mobile Hero (< lg) - Clean stacked design -->
    <div class="lg:hidden">
      <!-- Image or gradient header -->
      <div class="relative">
        <div 
          v-if="hasImage" 
          class="aspect-[16/10] bg-zinc-200 dark:bg-zinc-800"
        >
          <img 
            :src="heroImageUrl" 
            :alt="String(event.title)"
            class="w-full h-full object-cover"
          >
          <!-- Gradient fade at bottom -->
          <div class="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-white dark:from-zinc-950 to-transparent" />
        </div>
        
        <!-- Gradient header for no image -->
        <div 
          v-else 
          class="relative h-40 sm:h-48 bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900 overflow-hidden"
        >
          <!-- Subtle radial accent -->
          <div class="absolute inset-0 bg-[radial-gradient(ellipse_100%_100%_at_50%_0%,rgba(189,28,38,0.2),transparent)]" />
          
          <!-- Grid pattern -->
          <svg class="absolute inset-0 w-full h-full opacity-[0.06]" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <pattern id="mobile-hero-grid" width="40" height="40" patternUnits="userSpaceOnUse">
                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5"/>
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#mobile-hero-grid)" />
          </svg>
          
          <!-- Category/Tenant badges -->
          <div class="absolute top-4 left-4 flex items-center gap-2">
            <span 
              v-if="event.category" 
              class="px-2.5 py-1 text-xs font-semibold uppercase tracking-wide rounded-full bg-white/10 text-white/90 backdrop-blur-sm"
            >
              {{ event.category.name }}
            </span>
            <span 
              v-if="event.tenant" 
              class="px-2.5 py-1 text-xs font-medium rounded-full bg-vusa-red text-white"
            >
              {{ event.tenant.shortname }}
            </span>
          </div>
        </div>
      </div>

      <!-- Content section -->
      <div class="px-6 sm:px-8 -mt-4 relative z-10" :class="{ 'pt-4': !hasImage }">
        <!-- Status Badge -->
        <div v-if="eventStatus" class="mb-4">
          <span 
            class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-semibold rounded-full"
            :class="mobileStatusBadgeClasses"
          >
            <span class="relative flex h-2 w-2">
              <span 
                v-if="isLive" 
                class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                :class="mobileStatusPingClasses"
              />
              <span class="relative inline-flex rounded-full h-2 w-2" :class="mobileStatusDotClasses" />
            </span>
            {{ eventStatus }}
          </span>
        </div>

        <!-- Title -->
        <h1 class="text-2xl sm:text-3xl font-extrabold leading-tight text-zinc-900 dark:text-zinc-100 mb-6">
          {{ event.title }}
        </h1>

        <!-- Metadata -->
        <div class="space-y-4 text-sm mb-6">
          <!-- Date -->
          <div class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-vusa-red/10 dark:bg-vusa-red/20">
              <IFluentCalendarLtr20Regular class="w-5 h-5 text-vusa-red" />
            </div>
            <span class="font-medium">{{ formattedDateTime }}</span>
          </div>
          
          <!-- Location -->
          <a 
            v-if="event.location"
            :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(String(event.location))}`"
            target="_blank"
            class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300 group"
          >
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-vusa-red/10 dark:bg-vusa-red/20 group-hover:bg-vusa-red/20 dark:group-hover:bg-vusa-red/30 transition-colors">
              <IFluentLocation20Regular class="w-5 h-5 text-vusa-red" />
            </div>
            <span class="font-medium underline-offset-2 group-hover:underline">{{ event.location }}</span>
          </a>
          
          <!-- Organizer -->
          <div v-if="event.organizer || event.tenant" class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-vusa-red/10 dark:bg-vusa-red/20">
              <IFluentPeopleTeam20Regular class="w-5 h-5 text-vusa-red" />
            </div>
            <span class="font-medium">{{ event.organizer || event.tenant?.shortname }}</span>
          </div>
        </div>

        <!-- Action Buttons Slot -->
        <div v-if="$slots.actions" class="pt-4 pb-2">
          <slot name="actions" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { usePage } from '@inertiajs/vue3'

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

// Responsive height classes - taller hero for impact
const heroContainerClasses = computed(() => {
  if (hasImage.value) {
    return 'min-h-[480px] lg:min-h-[560px] xl:min-h-[640px]'
  } else {
    return 'min-h-[420px] lg:min-h-[500px] xl:min-h-[560px]'
  }
})

// Format event date and time
const formattedDateTime = computed(() => {
  const startDate = new Date(props.event.date)
  const endDate = props.event.end_date ? new Date(props.event.end_date) : null

  return formatDateRange(startDate, endDate || undefined, locale.value as any)
})

// Event status computation
const now = computed(() => new Date())
const eventDate = computed(() => new Date(props.event.date))
const endDate = computed(() => props.event.end_date ? new Date(props.event.end_date) : eventDate.value)

const isPast = computed(() => endDate.value < now.value)
const isLive = computed(() => eventDate.value <= now.value && endDate.value >= now.value)
const isUpcoming = computed(() => eventDate.value > now.value)

const eventStatus = computed(() => {
  if (isPast.value) {
    return $t('Renginys įvyko')
  } else if (isLive.value) {
    return $t('Vyksta dabar')
  } else if (isUpcoming.value) {
    const timeDiff = eventDate.value.getTime() - now.value.getTime()
    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24))

    if (daysDiff === 0) {
      return $t('Šiandien')
    } else if (daysDiff === 1) {
      return $t('Rytoj')
    } else if (daysDiff <= 7) {
      return $t('Šią savaitę')
    }
    return $t('Netrukus')
  }
  return null
})

// Desktop status badge styling
const statusBadgeClasses = computed(() => {
  if (isPast.value) {
    return 'bg-zinc-500/30 text-zinc-200 border border-zinc-500/30'
  } else if (isLive.value) {
    return 'bg-emerald-500/30 text-emerald-200 border border-emerald-500/30'
  } else {
    return 'bg-vusa-red/30 text-red-200 border border-vusa-red/30'
  }
})

const statusDotClasses = computed(() => {
  if (isPast.value) return 'bg-zinc-400'
  if (isLive.value) return 'bg-emerald-400'
  return 'bg-vusa-red'
})

const statusPingClasses = computed(() => {
  if (isLive.value) return 'bg-emerald-400'
  return 'bg-vusa-red'
})

// Mobile status badge styling
const mobileStatusBadgeClasses = computed(() => {
  if (isPast.value) {
    return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
  } else if (isLive.value) {
    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
  } else {
    return 'bg-vusa-red/10 text-vusa-red dark:bg-vusa-red/20'
  }
})

const mobileStatusDotClasses = computed(() => {
  if (isPast.value) return 'bg-zinc-400 dark:bg-zinc-500'
  if (isLive.value) return 'bg-emerald-500'
  return 'bg-vusa-red'
})

const mobileStatusPingClasses = computed(() => {
  if (isLive.value) return 'bg-emerald-500'
  return 'bg-vusa-red'
})
</script>

<style scoped>
/* Full-bleed hero - breaks out of wrapper grid */
.event-hero-wrapper {
  width: 100vw;
  position: relative;
  left: 50%;
  right: 50%;
  margin-left: -50vw;
  margin-right: -50vw;
}

/* Smooth reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .animate-ping {
    animation: none;
  }
}
</style>
