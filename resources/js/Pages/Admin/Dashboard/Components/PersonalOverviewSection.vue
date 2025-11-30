<template>
  <section class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 id="personal-overview-heading" class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
        {{ $t('Personal Overview') }}
      </h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Institution check-in card -->
      <InstitutionCheckInCard 
        :institutions 
        :is-admin 
        :max-display-count="5" 
        :current-user-id="String(currentUserId)"
        @show-all-modal="$emit('show-all-institutions')" 
        @refresh="$emit('refresh')"
        @create-meeting="$emit('create-meeting')"
        @schedule-meeting="$emit('schedule-meeting', $event)"
        @show-institution-details="$emit('show-institution-details', $event)" />

      <!-- Upcoming meetings card -->
      <UpcomingMeetingsCard 
        :upcoming-meetings 
        :institutions-insights 
        @show-all-meetings="$emit('show-all-meetings')"
        @create-meeting="$emit('create-meeting')" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import UpcomingMeetingsCard from './UpcomingMeetingsCard.vue';

import InstitutionCheckInCard from "@/Components/CheckIns/InstitutionCheckInCard.vue";

interface Props {
  institutions: App.Entities.Institution[];
  upcomingMeetings: App.Entities.Meeting[];
  institutionsInsights: any;
  isAdmin: boolean;
  currentUserId: number;
}

defineProps<Props>();

defineEmits<{
  'show-all-institutions': [];
  'show-all-meetings': [];
  'create-meeting': [];
  'schedule-meeting': [institutionId: string];
  'show-institution-details': [institutionId: string];
  'refresh': [];
}>();
</script>
