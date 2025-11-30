<template>
  <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg p-6 mb-6">
    <div class="flex items-start justify-between mb-4">
      <div class="flex items-center gap-4">
        <div
          class="h-16 w-16 bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-700 dark:to-zinc-800 rounded-lg flex items-center justify-center text-zinc-600 dark:text-zinc-300 text-lg font-medium border border-zinc-200 dark:border-zinc-600">
          {{ getInitials(institution.name) }}
        </div>
        <div class="flex-1">
          <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
            {{ institution.name }}
          </h1>
          <p v-if="institution.short_name" class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ institution.short_name }}
          </p>

          <!-- Managers in Header -->
          <div v-if="institution.managers?.length > 0" class="flex items-center gap-2 mt-2">
            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('Vadovai') }}:</span>
              <UsersAvatarGroup :users="institution.managers" :max="3" :size="24" />
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <Button v-if="canScheduleMeeting" variant="default" size="sm" class="gap-2" @click="$emit('schedule-meeting')">
          <Calendar class="h-4 w-4" />
          {{ $t('Suplanuoti susitikimą') }}
        </Button>

        <Button v-if="canAddCheckIn" variant="outline" size="sm" class="gap-2" @click="$emit('add-check-in')">
          <Clock class="h-4 w-4" />
          {{ $t('Pridėti pažymą') }}
        </Button>

        <slot name="actions" />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <!-- Active Members -->
      <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
          <Users class="h-4 w-4 text-blue-500" />
          <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $t('Aktyvūs nariai') }}
          </span>
        </div>
        <div class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
          {{ activeMembers.length }}
        </div>
        <div class="text-xs text-zinc-500 dark:text-zinc-400">
          {{ $t('iš') }} {{ totalPositions }} {{ $t('pozicijų') }}
        </div>
      </div>

      <!-- Last Meeting -->
      <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
          <Calendar class="h-4 w-4 text-green-500" />
          <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $t('Paskutinis susitikimas') }}
          </span>
        </div>
        <div v-if="lastMeeting" class="text-sm text-zinc-900 dark:text-zinc-100">
          {{ formatRelativeTime(lastMeeting.start_time) }}
        </div>
        <div v-else class="text-sm text-zinc-500 dark:text-zinc-400">
          {{ $t('Nėra duomenų') }}
        </div>
      </div>

      <!-- Check-in Status -->
      <!-- <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4"> -->
      <!--   <div class="flex items-center gap-2 mb-2"> -->
      <!--     <Clock class="h-4 w-4 text-amber-500" /> -->
      <!--     <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"> -->
      <!--       {{ $t('Pažymos statusas') }} -->
      <!--     </span> -->
      <!--   </div> -->
      <!--   <div v-if="activeCheckIn" class="text-sm"> -->
      <!--     <div class="text-zinc-900 dark:text-zinc-100"> -->
      <!--       {{ $t('Iki') }} {{ formatDate(activeCheckIn.until_date) }} -->
      <!--     </div> -->
      <!--     <div class="text-xs text-zinc-500 dark:text-zinc-400"> -->
      <!--       {{ activeCheckIn.user?.name }} -->
      <!--     </div> -->
      <!--   </div> -->
      <!--   <div v-else class="text-sm text-zinc-500 dark:text-zinc-400"> -->
      <!--     {{ $t('Nėra aktyvių') }} -->
      <!--   </div> -->
      <!-- </div> -->

      <!-- Total Meetings -->
      <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4">
        <div class="flex items-center gap-2 mb-2">
          <BarChart3 class="h-4 w-4 text-purple-500" />
          <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $t('Susitikimų') }}
          </span>
        </div>
        <div class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
          {{ meetings.length }}
        </div>
        <div class="text-xs text-zinc-500 dark:text-zinc-400">
          {{ $t('šiais metais') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Calendar, Clock, Users, BarChart3 } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import UserPopover from '@/Components/Avatars/UserPopover.vue';
import UsersAvatarGroup from '../Avatars/UsersAvatarGroup.vue';

const props = defineProps<{
  institution: App.Entities.Institution & {
    managers?: App.Entities.User[];
  };
  meetings: App.Entities.Meeting[];
  activeCheckIn?: App.Entities.InstitutionCheckIn | null;
  canScheduleMeeting?: boolean;
  canAddCheckIn?: boolean;
}>();

const emit = defineEmits<{
  'schedule-meeting': [];
  'add-check-in': [];
}>();

const activeMembers = computed(() => {
  return props.institution.current_users || [];
});

const totalPositions = computed(() => {
  return props.institution.duties?.reduce((sum, duty) => {
    return sum + (duty.places_to_occupy || 0);
  }, 0) || 0;
});

const lastMeeting = computed(() => {
  if (!props.meetings.length) return null;
  return [...props.meetings]
    .sort((a, b) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime())[0];
});

const formatRelativeTime = (dateString: string) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInDays = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));

  if (diffInDays === 0) return $t('Šiandien');
  if (diffInDays === 1) return $t('Vakar');
  if (diffInDays < 7) return `${$t('Prieš')} ${diffInDays} ${$t('dienas')}`;
  if (diffInDays < 30) {
    const weeks = Math.floor(diffInDays / 7);
    return `${$t('Prieš')} ${weeks} ${weeks === 1 ? $t('savaitę') : $t('savaites')}`;
  }
  return date.toLocaleDateString();
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const getInitials = (name?: string) => {
  if (!name) return 'IN';

  const words = name.split(' ').filter(word => word.length > 0);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  if (words.length === 1) {
    return words[0].substring(0, 2).toUpperCase();
  }
  return 'IN';
};
</script>
