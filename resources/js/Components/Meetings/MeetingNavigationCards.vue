<template>
  <div class="flex gap-3">
    <!-- Previous Meeting -->
    <Link
      v-if="previousMeeting"
      :href="route('meetings.show', previousMeeting.id)"
      :class="[
        'flex-1 flex items-center gap-3 p-3 rounded-lg',
        'border border-zinc-200 dark:border-zinc-800',
        'bg-zinc-50/50 dark:bg-zinc-800/30',
        'hover:bg-zinc-100 dark:hover:bg-zinc-800/50',
        'hover:border-zinc-300 dark:hover:border-zinc-700',
        'transition-all duration-200 group',
      ]"
    >
      <div
        :class="[
          'flex items-center justify-center',
          'w-10 h-10 rounded-lg shrink-0',
          'bg-white dark:bg-zinc-900',
          'border border-zinc-200 dark:border-zinc-700',
          'group-hover:border-primary/50 dark:group-hover:border-primary/50',
          'transition-colors duration-200',
        ]"
      >
        <ChevronLeft class="h-5 w-5 text-zinc-400 group-hover:text-primary transition-colors" />
      </div>
      <div class="flex-1 min-w-0">
        <span class="block text-xs text-zinc-500 dark:text-zinc-400 mb-0.5">
          {{ $t('Ankstesnis') }}
        </span>
        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate">
          {{ formatNavDate(previousMeeting.start_time) }}
        </p>
      </div>
    </Link>

    <!-- Spacer if only one exists -->
    <div v-if="!previousMeeting && nextMeeting" class="flex-1" />

    <!-- Next Meeting -->
    <Link
      v-if="nextMeeting"
      :href="route('meetings.show', nextMeeting.id)"
      :class="[
        'flex-1 flex items-center gap-3 p-3 rounded-lg',
        'border border-zinc-200 dark:border-zinc-800',
        'bg-zinc-50/50 dark:bg-zinc-800/30',
        'hover:bg-zinc-100 dark:hover:bg-zinc-800/50',
        'hover:border-zinc-300 dark:hover:border-zinc-700',
        'transition-all duration-200 group',
      ]"
    >
      <div class="flex-1 min-w-0 text-right">
        <span class="block text-xs text-zinc-500 dark:text-zinc-400 mb-0.5">
          {{ $t('Kitas') }}
        </span>
        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate">
          {{ formatNavDate(nextMeeting.start_time) }}
        </p>
      </div>
      <div
        :class="[
          'flex items-center justify-center',
          'w-10 h-10 rounded-lg shrink-0',
          'bg-white dark:bg-zinc-900',
          'border border-zinc-200 dark:border-zinc-700',
          'group-hover:border-primary/50 dark:group-hover:border-primary/50',
          'transition-colors duration-200',
        ]"
      >
        <ChevronRight class="h-5 w-5 text-zinc-400 group-hover:text-primary transition-colors" />
      </div>
    </Link>

    <!-- Spacer if only one exists -->
    <div v-if="previousMeeting && !nextMeeting" class="flex-1" />
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

import { formatStaticTime } from '@/Utils/IntlTime'

interface MeetingNav {
  id: string
  start_time: string
}

defineProps<{
  previousMeeting?: MeetingNav | null
  nextMeeting?: MeetingNav | null
}>()

const formatNavDate = (date: string) => {
  return formatStaticTime(new Date(date), {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>
