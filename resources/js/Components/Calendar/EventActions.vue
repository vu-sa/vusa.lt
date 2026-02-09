<template>
  <div class="event-actions" :class="layoutClasses">
    <!-- Consolidated Layout -->
    <template v-if="variant === 'consolidated'">
      <!-- Primary Action -->
      <div v-if="(event as any).url" class="flex-1 sm:flex-none">
        <Button size="lg" class="w-full gap-2 font-semibold sm:w-auto sm:px-8" :class="primaryButtonClasses" as="a"
          :href="(event as any).url" target="_blank">
          <component :is="primaryIcon" class="h-5 w-5" />
          {{ primaryActionText }}
        </Button>
      </div>

      <!-- Secondary Actions -->
      <div class="flex gap-3">
        <!-- Google Calendar -->
        <Button v-if="googleLink" variant="outline" size="lg"
          class="gap-2 border-zinc-300 text-zinc-700 transition-all duration-200 hover:border-zinc-400 hover:bg-zinc-50 hover:shadow-sm dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
          as="a" :href="googleLink" target="_blank" rel="noopener noreferrer" :title="$t('Įsidėk į Google kalendorių')">
          <IMdiGoogle class="h-4 w-4" />
          <span class="hidden md:inline font-medium">{{ $t('Kalendorius') }}</span>
        </Button>

        <!-- Facebook Event -->
        <Button v-if="event.facebook_url" variant="outline" size="lg"
          class="gap-2 border-zinc-300 text-zinc-700 transition-all duration-200 hover:border-zinc-400 hover:bg-zinc-50 hover:shadow-sm dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
          as="a" :href="event.facebook_url" target="_blank" rel="noopener noreferrer" :title="$t('Facebook renginys')">
          <IMdiFacebook class="h-4 w-4" />
          <span class="hidden md:inline font-medium">Facebook</span>
        </Button>

        <!-- Share Button -->
        <Button variant="outline" size="lg"
          class="gap-2 border-zinc-300 text-zinc-700 transition-all duration-200 hover:border-zinc-400 hover:bg-zinc-50 hover:shadow-sm dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
          :title="$t('Dalinkis')" @click="handleNativeShare">
          <IFluentShare20Regular class="h-4 w-4" />
          <span class="hidden md:inline font-medium">{{ $t('Dalinkis') }}</span>
        </Button>
      </div>
    </template>

    <!-- Other Variants -->
    <template v-else>
      <!-- Primary Action Section -->
      <div v-if="hasPrimaryAction" class="primary-actions">
        <!-- Participate Button -->
        <Button v-if="(event as any).url" :size="isMobile ? 'default' : 'lg'" class="w-full gap-2 font-semibold"
          :class="primaryButtonClasses" as="a" :href="(event as any).url" target="_blank">
          <component :is="primaryIcon" class="h-5 w-5" />
          {{ primaryActionText }}
        </Button>

        <!-- Event Detail Link (fallback) -->
        <Button v-else-if="showDetailButton" :size="isMobile ? 'default' : 'lg'" variant="outline" class="w-full gap-2"
          as="a" :href="detailUrl">
          <IFluentInfo20Regular class="h-5 w-5" />
          {{ $t('Plačiau apie renginį') }}
        </Button>
      </div>

      <!-- Secondary Actions -->
      <div v-if="hasSecondaryActions" class="secondary-actions">
        <!-- Quick Actions Row -->
        <div class="flex gap-2">
          <!-- Google Calendar -->
          <Button v-if="googleLink" :size="isMobile ? 'default' : 'lg'" variant="outline" class="flex-1 gap-2" as="a"
            :href="googleLink" target="_blank" rel="noopener noreferrer" :title="$t('Įsidėk į Google kalendorių')">
            <IMdiGoogle class="h-4 w-4" />
            <span class="hidden sm:inline">{{ $t('Kalendorius') }}</span>
          </Button>

          <!-- Facebook Event -->
          <Button v-if="event.facebook_url" :size="isMobile ? 'default' : 'lg'" variant="outline" class="flex-1 gap-2"
            as="a" :href="event.facebook_url" target="_blank" rel="noopener noreferrer"
            :title="$t('Facebook renginys')">
            <IMdiFacebook class="h-4 w-4" />
            <span class="hidden sm:inline">Facebook</span>
          </Button>

          <!-- Native Share (Mobile) -->
          <Button v-if="supportsNativeShare && isMobile" :size="isMobile ? 'default' : 'lg'" variant="outline"
            class="flex-1 gap-2" :title="$t('Dalinkis')" @click="handleNativeShare">
            <IFluentShare20Regular class="h-4 w-4" />
            <span class="hidden sm:inline">{{ $t('Dalinkis') }}</span>
          </Button>
        </div>
      </div>
    </template>

    <!-- Additional Info (Mobile) -->
    <div v-if="isMobile && showAdditionalInfo" class="additional-info">
      <div class="flex items-center justify-between text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ $t('Renginys') }} #{{ event.id }}</span>
        <span v-if="event.created_at">
          {{ $t('Paskelbta') }} {{ formatEventDate(new Date(event.created_at), locale) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';

import { formatEventDate } from '@/Utils/IntlTime';
import Button from '@/Components/ui/button/Button.vue';

interface Props {
  event: App.Entities.Calendar;
  googleLink?: string;
  isMobile?: boolean;
  isSticky?: boolean;
  showDetailButton?: boolean;
  detailUrl?: string;
  variant?: 'default' | 'compact' | 'sidebar' | 'consolidated';
}

const props = withDefaults(defineProps<Props>(), {
  isMobile: false,
  isSticky: false,
  showDetailButton: true,
  variant: 'default',
});

const page = usePage();
const locale = computed(() => page.props.app.locale);

// Check if browser supports native share API
const supportsNativeShare = computed(() => {
  return typeof navigator !== 'undefined' && 'share' in navigator;
});

// Layout classes based on variant and context
const layoutClasses = computed(() => {
  const classes = [];

  if (props.variant === 'consolidated') {
    classes.push('flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between');
  }
  else if (props.variant === 'sidebar') {
    classes.push('space-y-4 p-4 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm');
  }
  else if (props.variant === 'compact') {
    classes.push('space-y-2');
  }
  else {
    classes.push('space-y-4');
  }

  if (props.isSticky && props.isMobile) {
    classes.push(
      'fixed bottom-0 left-0 right-0 z-40',
      'bg-white/95 dark:bg-zinc-900/95 backdrop-blur-sm',
      'border-t border-zinc-200 dark:border-zinc-700',
      'p-4 pb-safe-bottom',
    );
  }

  return classes.join(' ');
});

// Primary action availability
const hasPrimaryAction = computed(() => {
  return (props.event as any).url || props.showDetailButton;
});

// Secondary actions availability
const hasSecondaryActions = computed(() => {
  return props.googleLink || props.event.facebook_url || supportsNativeShare.value;
});

// Show additional info on mobile
const showAdditionalInfo = computed(() => {
  return props.isMobile && !props.isSticky && props.variant !== 'compact';
});

// Event status helpers
const isEventPast = computed(() => {
  const now = new Date();
  const eventEndDate = props.event.end_date ? new Date(props.event.end_date) : new Date(props.event.date);
  return eventEndDate < now;
});

const isEventActive = computed(() => {
  const now = new Date();
  const eventStartDate = new Date(props.event.date);
  const eventEndDate = props.event.end_date ? new Date(props.event.end_date) : eventStartDate;
  return eventStartDate <= now && eventEndDate >= now;
});

// Primary action text and styling
const primaryActionText = computed(() => {
  if (isEventPast.value) {
    return $t('Renginys įvyko');
  }
  else if (isEventActive.value) {
    return $t('Dalyvauk dabar');
  }
  else if ((props.event as any).url) {
    return $t('Dalyvauk renginyje');
  }
  else {
    return $t('Daugiau informacijos');
  }
});

const primaryIcon = computed(() => {
  if (isEventPast.value) {
    return 'IFluentCheckmarkCircle20Regular';
  }
  else if (isEventActive.value) {
    return 'IFluentPlay20Filled';
  }
  else {
    return 'IFluentPersonAdd20Regular';
  }
});

const primaryButtonClasses = computed(() => {
  if (isEventPast.value) {
    return 'opacity-75 cursor-not-allowed pointer-events-none bg-zinc-500 hover:bg-zinc-500';
  }
  else if (isEventActive.value) {
    return 'bg-green-600 hover:bg-green-700 text-white animate-pulse';
  }
  else {
    return ''; // Default styling
  }
});

// Native share functionality
const handleNativeShare = async () => {
  if (!supportsNativeShare.value) return;

  try {
    const eventTitle = Array.isArray(props.event.title) ? props.event.title.join(' ') : (props.event.title || '');
    const eventDate = new Date(props.event.date);

    await navigator.share({
      title: eventTitle,
      text: `${eventTitle} - ${formatEventDate(eventDate, locale.value)}`,
      url: window.location.href,
    });
  }
  catch (error) {
    console.log('Share cancelled or failed:', error);
    // Fallback to clipboard copy
    await navigator.clipboard.writeText(window.location.href);
    // Could show a toast notification here
  }
};
</script>

<style scoped>
/* Safe area for mobile devices with notches */
.pb-safe-bottom {
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
}

/* Smooth animations for interactive states */
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {

  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.8;
  }
}

/* Backdrop blur support */
.backdrop-blur-sm {
  backdrop-filter: blur(4px);
}

/* Responsive gaps */
.space-y-4> :not([hidden])~ :not([hidden]) {
  margin-top: 1rem;
}

.space-y-2> :not([hidden])~ :not([hidden]) {
  margin-top: 0.5rem;
}
</style>
