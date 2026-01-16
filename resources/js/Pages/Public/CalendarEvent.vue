<template>
  <div class="calendar-event-page min-h-screen bg-white dark:bg-zinc-900">
    <!-- Hero Section - Full Bleed -->
    <EventHero :event="event">
      <template #actions>
        <div class="flex flex-wrap gap-3">
          <!-- Primary CTA -->
          <Button 
            v-if="(event as any).url && !isPast" 
            size="lg"
            class="gap-2.5 font-semibold px-6 shadow-lg shadow-black/20 hover:scale-[1.02] transition-transform"
            :class="primaryButtonClasses"
            as="a"
            :href="(event as any).url" 
            target="_blank"
          >
            <IFluentPersonAdd20Regular v-if="!isLive" class="w-5 h-5" />
            <IFluentPlay20Filled v-else class="w-5 h-5" />
            {{ primaryActionText }}
          </Button>

          <!-- Google Calendar - Desktop only in hero -->
          <Button 
            v-if="googleLink" 
            variant="outline" 
            size="lg"
            class="hidden lg:inline-flex gap-2 bg-white/20 border-white/40 text-white hover:bg-white/30 hover:border-white/60 backdrop-blur-md transition-all"
            as="a" 
            :href="googleLink" 
            target="_blank" 
            rel="noopener noreferrer"
          >
            <IMdiGoogle class="w-5 h-5" />
            {{ $t('Į kalendorių') }}
          </Button>

          <!-- Facebook Event - Desktop only in hero -->
          <Button 
            v-if="event.facebook_url" 
            variant="outline" 
            size="lg"
            class="hidden lg:inline-flex gap-2 bg-white/20 border-white/40 text-white hover:bg-white/30 hover:border-white/60 backdrop-blur-md transition-all"
            as="a" 
            :href="event.facebook_url" 
            target="_blank" 
            rel="noopener noreferrer"
          >
            <IMdiFacebook class="w-5 h-5" />
            Facebook
          </Button>

          <!-- Share Button - Desktop only in hero -->
          <Button 
            variant="outline" 
            size="lg"
            class="hidden lg:inline-flex gap-2 bg-white/20 border-white/40 text-white hover:bg-white/30 hover:border-white/60 backdrop-blur-md transition-all"
            @click="handleShare"
          >
            <IFluentShare20Regular class="w-5 h-5" />
            {{ $t('Dalinkis') }}
          </Button>
        </div>
      </template>
    </EventHero>

    <!-- Main Content Area -->
    <div class="wrapper">
      <div class="py-10 lg:py-16">
        <!-- Two Column Layout -->
        <div class="grid lg:grid-cols-12 gap-10 lg:gap-16">
          <!-- Main Content -->
          <main class="lg:col-span-8 space-y-12">
            <!-- Description Section -->
            <section>
              <div class="flex items-center gap-4 mb-6">
                <div class="w-1.5 h-8 bg-vusa-red rounded-full" />
                <h2 class="text-xl lg:text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                  {{ $t("Apie renginį") }}
                </h2>
              </div>
              <div 
                class="prose prose-zinc max-w-none dark:prose-invert prose-lg prose-headings:font-bold prose-p:text-zinc-600 dark:prose-p:text-zinc-400 prose-a:text-vusa-red prose-a:no-underline hover:prose-a:underline"
                v-html="event.description" 
              />
            </section>

            <!-- Video Section -->
            <section v-if="event.video_url">
              <div class="flex items-center gap-4 mb-6">
                <div class="w-1.5 h-8 bg-vusa-red rounded-full" />
                <h2 class="text-xl lg:text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                  {{ $t("Video") }}
                </h2>
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
            <section v-if="hasImages && normalizedImages.length > 1">
              <EventImageGallery :images="normalizedImages" :event-title="String(event.title)" />
            </section>
          </main>

          <!-- Sidebar -->
          <aside class="lg:col-span-4 space-y-8">
            <!-- Mobile Action Buttons (shown above sidebar on mobile) -->
            <div class="lg:hidden">
              <div class="flex flex-wrap gap-2">
                <Button 
                  v-if="googleLink" 
                  variant="outline" 
                  size="default"
                  class="flex-1 gap-2"
                  as="a" 
                  :href="googleLink" 
                  target="_blank"
                >
                  <IMdiGoogle class="w-4 h-4" />
                  {{ $t('Kalendorius') }}
                </Button>
                <Button 
                  v-if="event.facebook_url" 
                  variant="outline" 
                  size="default"
                  class="flex-1 gap-2"
                  as="a" 
                  :href="event.facebook_url" 
                  target="_blank"
                >
                  <IMdiFacebook class="w-4 h-4" />
                  Facebook
                </Button>
                <Button 
                  variant="outline" 
                  size="default"
                  class="gap-2"
                  @click="handleShare"
                >
                  <IFluentShare20Regular class="w-4 h-4" />
                </Button>
              </div>
            </div>

            <!-- Share Card - Desktop only -->
            <div class="hidden lg:block p-5 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 ring-1 ring-zinc-900/5 dark:ring-white/5">
              <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-4 flex items-center gap-2">
                <IFluentShare20Regular class="w-4 h-4 text-vusa-red" />
                {{ $t("Dalinkis renginiu") }}
              </h3>
              <div class="flex gap-2">
                <Button 
                  v-if="googleLink" 
                  variant="outline" 
                  size="sm"
                  class="flex-1 gap-2 text-xs"
                  as="a" 
                  :href="googleLink" 
                  target="_blank"
                >
                  <IMdiGoogle class="w-4 h-4" />
                  Google
                </Button>
                <Button 
                  v-if="event.facebook_url" 
                  variant="outline" 
                  size="sm"
                  class="flex-1 gap-2 text-xs"
                  as="a" 
                  :href="event.facebook_url" 
                  target="_blank"
                >
                  <IMdiFacebook class="w-4 h-4" />
                  Facebook
                </Button>
                <Button 
                  variant="outline" 
                  size="sm"
                  class="gap-2 text-xs"
                  @click="handleShare"
                >
                  <IFluentShare20Regular class="w-4 h-4" />
                </Button>
              </div>
            </div>

            <!-- Upcoming Events -->
            <div class="lg:sticky lg:top-8">
              <UpcomingEventsCompact 
                :events="calendar" 
                :locale="$page.props.app.locale"
                :exclude-event-id="event.id"
                :max-visible="5"
              />
            </div>
          </aside>
        </div>
      </div>
    </div>

    <!-- Mobile Sticky Action Bar -->
    <div 
      v-if="(event as any).url && !isPast" 
      class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/98 dark:bg-zinc-900/98 backdrop-blur-md border-t border-zinc-200/80 dark:border-zinc-700/60 p-4 pb-safe shadow-[0_-4px_20px_-4px_rgba(0,0,0,0.1)] dark:shadow-[0_-4px_20px_-4px_rgba(0,0,0,0.4)]"
    >
      <div class="flex gap-3">
        <!-- Primary CTA -->
        <Button 
          size="lg"
          class="flex-1 gap-2 font-semibold"
          :class="mobilePrimaryButtonClasses"
          as="a"
          :href="(event as any).url" 
          target="_blank"
        >
          <IFluentPersonAdd20Regular v-if="!isLive" class="w-5 h-5" />
          <IFluentPlay20Filled v-else class="w-5 h-5" />
          {{ primaryActionText }}
        </Button>
        
        <!-- Secondary actions -->
        <Button 
          v-if="googleLink" 
          variant="outline" 
          size="lg"
          as="a" 
          :href="googleLink" 
          target="_blank"
        >
          <IMdiGoogle class="w-5 h-5" />
        </Button>
        
        <Button 
          variant="outline" 
          size="lg"
          @click="handleShare"
        >
          <IFluentShare20Regular class="w-5 h-5" />
        </Button>
      </div>
    </div>

    <!-- Bottom spacer for mobile sticky bar -->
    <div v-if="(event as any).url && !isPast" class="lg:hidden h-24" />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

import EventHero from "@/Components/Calendar/EventHero.vue";
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

// Set up breadcrumbs
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
const now = computed(() => new Date())
const eventDate = computed(() => new Date(props.event.date))
const endDate = computed(() => props.event.end_date ? new Date(props.event.end_date) : eventDate.value)

const isPast = computed(() => endDate.value < now.value)
const isLive = computed(() => eventDate.value <= now.value && endDate.value >= now.value)

// Primary action helpers
const primaryActionText = computed(() => {
  if (isLive.value) return $t('Dalyvauk dabar')
  return $t('Registruotis')
})

const primaryButtonClasses = computed(() => {
  if (isLive.value) return 'bg-emerald-500 hover:bg-emerald-600 text-white border-0'
  return 'bg-vusa-red hover:bg-red-700 text-white border-0'
})

const mobilePrimaryButtonClasses = computed(() => {
  if (isLive.value) return 'bg-emerald-500 hover:bg-emerald-600 text-white'
  return ''
})

// Check if event has gallery images
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

// Share handler with native share API fallback
const handleShare = async () => {
  const eventTitle = Array.isArray(props.event.title) ? props.event.title.join(' ') : (props.event.title || '')
  const shareData = {
    title: eventTitle,
    text: eventTitle,
    url: window.location.href
  }

  if (typeof navigator !== 'undefined' && 'share' in navigator) {
    try {
      await navigator.share(shareData)
    } catch {
      await copyToClipboard()
    }
  } else {
    await copyToClipboard()
  }
}

const copyToClipboard = async () => {
  try {
    await navigator.clipboard.writeText(window.location.href)
  } catch (error) {
    console.error('Failed to copy to clipboard:', error)
  }
}
</script>

<style scoped>
/* Mobile safe area for devices with notches/home indicators */
.pb-safe {
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
}
</style>
