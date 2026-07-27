<template>
  <div
    data-tour="institution-item"
    class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white/60 dark:bg-zinc-800/50 transition-all duration-200 hover:shadow-sm hover:bg-white/80 dark:hover:bg-zinc-700/50 hover:border-zinc-300 dark:hover:border-zinc-600">
    <!-- Left: Institution Info -->
    <div class="flex-1 min-w-0 mr-3">
      <div class="flex items-center gap-2.5 mb-1.5">
        <!-- Status dot -->
        <div class="w-3 h-3 rounded-full shrink-0 ring-2 ring-white dark:ring-zinc-700" :class="statusDotClass" />

        <!-- Institution name -->
        <InertiaLink :href="route('institutions.show', institution.id)"
          class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 truncate">
          {{ institution.name }}
        </InertiaLink>
        <!-- Public meetings indicator -->
        <TooltipProvider v-if="institution.has_public_meetings">
          <Tooltip>
            <TooltipTrigger as-child>
              <Globe class="h-3.5 w-3.5 text-green-600 dark:text-green-400 shrink-0" />
            </TooltipTrigger>
            <TooltipContent>{{ $t('Vieši posėdžiai') }}</TooltipContent>
          </Tooltip>
        </TooltipProvider>
        <!-- Muted indicator -->
        <TooltipProvider v-if="isMuted">
          <Tooltip>
            <TooltipTrigger as-child>
              <BellOff class="h-3.5 w-3.5 text-zinc-400 dark:text-zinc-500 shrink-0" />
            </TooltipTrigger>
            <TooltipContent>{{ $t('visak.notifications_muted') }}</TooltipContent>
          </Tooltip>
        </TooltipProvider>
        <!-- Duty-based badge -->
        <span v-if="isDutyBased && showSubscriptionActions"
          class="text-xs px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 font-medium shrink-0">
          {{ $t('visak.duty_based') }}
        </span>
      </div>

      <!-- Status row -->
      <div class="flex items-center gap-2 ml-5">
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger as-child>
              <span :class="statusBadgeClass">
                <component :is="statusIcon" class="h-3 w-3 shrink-0" />
                <span class="hidden sm:inline">{{ statusLabel }}</span>
                <span v-if="activityStatus.status === 'covered_by_upcoming_meeting' && activityStatus.next_meeting_at"
                  class="opacity-75">
                  {{ formatDate(activityStatus.next_meeting_at) }}
                </span>
                <span v-else-if="activityStatus.status === 'covered_by_check_in' && activityStatus.active_check_in_until"
                  class="hidden opacity-75 sm:inline">
                  {{ $t('iki') }} {{ formatDate(activityStatus.active_check_in_until) }}
                </span>
              </span>
            </TooltipTrigger>
            <TooltipContent side="top" class="max-w-xs">
              <div>{{ statusLabel }}</div>
              <template v-if="activityStatus.status === 'covered_by_check_in'">
                <span v-if="activityStatus.active_check_in_until">
                  {{ $t('iki') }} {{ formatDate(activityStatus.active_check_in_until) }}
                </span>
                <p v-if="institution.active_check_in?.note" class="mt-1 text-xs opacity-80">
                  {{ institution.active_check_in.note }}
                </p>
              </template>
              <template v-else-if="activityStatus.effective_days_since_activity !== null">
                {{ activityStatus.effective_days_since_activity }} {{ $t('d.') }} /
                {{ activityStatus.periodicity_days }} {{ $t('d.') }}
              </template>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>

        <span v-if="activityStatus.last_activity_at && !activityStatus.next_meeting_at"
          class="text-xs text-zinc-500 dark:text-zinc-400">
          <template v-if="activityStatus.last_activity_type === 'check_in'">
            {{ $t('visak.activity.activity_status.reported_until', {
              date: formatDate(activityStatus.last_activity_at),
            }) }}
          </template>
          <template v-else>
            {{ formatDate(activityStatus.last_activity_at) }}
          </template>
          <span v-if="activityStatus.effective_days_since_activity !== null"
            :class="activityDaysClass">
            ({{ activityStatus.effective_days_since_activity }} {{ $t('d.') }})
          </span>
        </span>
      </div>
    </div>

    <!-- Right: Action Buttons (optional) -->
    <div v-if="showActions" class="flex items-center gap-2 flex-shrink-0">
      <!-- Schedule Meeting button -->
      <TooltipProvider v-if="canScheduleMeeting">
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="default" size="sm" class="h-8 w-8" @click="emit('schedule-meeting', institution.id)">
              <component :is="MeetingIconFilled" class="h-3.5 w-3.5" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('Suplanuoti naują susitikimą šiai institucijai') }}</TooltipContent>
        </Tooltip>
      </TooltipProvider>

      <!-- Add Check-in button -->
      <TooltipProvider v-if="canAddCheckIn && !institution.active_check_in">
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="ghost" size="sm"
              class="h-8 w-8 opacity-60 hover:opacity-100 hover:bg-amber-50 hover:text-amber-700 dark:hover:bg-amber-900/30 dark:hover:text-amber-400 transition-all"
              @click="emit('add-check-in', institution.id)">
              <CalendarOff class="h-3.5 w-3.5" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('Pranešti apie posėdžio nebuvimą') }}</TooltipContent>
        </Tooltip>
      </TooltipProvider>

      <!-- Remove Active Check-in if exists -->
      <TooltipProvider v-if="institution.active_check_in">
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="ghost" size="sm"
              class="h-8 w-8 opacity-60 hover:opacity-100 hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-900/30 dark:hover:text-rose-400 transition-all"
              @click="emit('remove-active-check-in', institution.id)">
              <component :is="X" class="h-3.5 w-3.5" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('Pašalinti pranešimą apie posėdžio nebuvimą') }}</TooltipContent>
        </Tooltip>
      </TooltipProvider>

      <!-- Subscription Actions (only for non-duty-based institutions) -->
      <template v-if="canShowSubscriptionActions">
        <!-- Divider -->
        <div class="w-px h-6 bg-zinc-200 dark:bg-zinc-700 mx-1" />

        <!-- Follow/Unfollow button -->
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger as-child>
              <Button variant="ghost" size="sm"
                class="h-8 w-8 opacity-60 hover:opacity-100 transition-all"
                :class="isFollowed ? 'text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                :disabled="followLoading"
                @click="emit('toggle-follow', institution.id)">
                <Loader2 v-if="followLoading" class="h-3.5 w-3.5 animate-spin" />
                <Eye v-else-if="isFollowed" class="h-3.5 w-3.5" />
                <EyeOff v-else class="h-3.5 w-3.5" />
              </Button>
            </TooltipTrigger>
            <TooltipContent>
              {{ isFollowed ? $t('visak.unfollow') : $t('visak.follow') }}
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>

        <!-- Mute/Unmute button (only show if followed) -->
        <TooltipProvider v-if="isFollowed">
          <Tooltip>
            <TooltipTrigger as-child>
              <Button variant="ghost" size="sm"
                class="h-8 w-8 opacity-60 hover:opacity-100 transition-all"
                :class="isMuted ? 'text-zinc-500 dark:text-zinc-400' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                :disabled="muteLoading"
                @click="emit('toggle-mute', institution.id)">
                <Loader2 v-if="muteLoading" class="h-3.5 w-3.5 animate-spin" />
                <BellOff v-else-if="isMuted" class="h-3.5 w-3.5" />
                <Bell v-else class="h-3.5 w-3.5" />
              </Button>
            </TooltipTrigger>
            <TooltipContent>
              {{ isMuted ? $t('visak.unmute') : $t('visak.mute') }}
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Link as InertiaLink } from '@inertiajs/vue3';
import { AlertTriangle, Bell, BellOff, CalendarCheck, CalendarClock, CalendarOff, CalendarX, CheckCircle2, Clock, Eye, EyeOff, Globe, Loader2, X } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { formatStaticTime } from '@/Utils/IntlTime';
import type { AtstovavimasInstitution } from '@/Pages/Admin/Dashboard/types';
import { MeetingIconFilled } from '@/Components/icons';

const props = defineProps<{
  institution: AtstovavimasInstitution;
  showActions?: boolean;
  showSubscriptionActions?: boolean;
  canScheduleMeeting?: boolean;
  canAddCheckIn?: boolean;
  followLoading?: boolean;
  muteLoading?: boolean;
}>();

const emit = defineEmits<{
  'schedule-meeting': [institutionId: string];
  'add-check-in': [institutionId: string];
  'remove-active-check-in': [institutionId: string];
  'toggle-follow': [institutionId: string];
  'toggle-mute': [institutionId: string];
}>();

const activityStatus = computed(() => props.institution.activity_status);
const statusLabel = computed(() => $t(`visak.activity.activity_status.${activityStatus.value.status}`));

const statusIcon = computed(() => {
  return {
    no_activity: CalendarX,
    healthy: CheckCircle2,
    approaching: Clock,
    overdue: AlertTriangle,
    covered_by_upcoming_meeting: CalendarClock,
    covered_by_check_in: CalendarCheck,
  }[activityStatus.value.status];
});

const statusDotClass = computed(() => {
  return {
    no_activity: 'bg-zinc-400',
    healthy: 'bg-emerald-400',
    approaching: 'bg-amber-400',
    overdue: 'bg-orange-400',
    covered_by_upcoming_meeting: 'bg-blue-400',
    covered_by_check_in: 'bg-emerald-400',
  }[activityStatus.value.status];
});

const statusBadgeClass = computed(() => {
  const base = 'inline-flex shrink-0 items-center gap-1 rounded-full border px-1.5 py-1 text-xs font-medium sm:gap-1.5 sm:px-2.5 sm:py-1.5';
  const color = {
    no_activity: 'border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
    healthy: 'border-emerald-200 bg-emerald-100 text-emerald-800 dark:border-emerald-700/50 dark:bg-emerald-900/40 dark:text-emerald-300',
    approaching: 'border-amber-200 bg-amber-100 text-amber-700 dark:border-amber-700/50 dark:bg-amber-900/40 dark:text-amber-300',
    overdue: 'border-orange-200 bg-orange-100 text-orange-700 dark:border-orange-700/50 dark:bg-orange-900/40 dark:text-orange-300',
    covered_by_upcoming_meeting: 'border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-700/50 dark:bg-blue-900/40 dark:text-blue-300',
    covered_by_check_in: 'border-emerald-200 bg-emerald-100 text-emerald-800 dark:border-emerald-700/50 dark:bg-emerald-900/40 dark:text-emerald-300',
  }[activityStatus.value.status];

  return `${base} ${color}`;
});

const activityDaysClass = computed(() => {
  if (activityStatus.value.status === 'overdue') {
    return 'font-medium text-orange-600 dark:text-orange-400';
  }
  if (activityStatus.value.status === 'approaching') {
    return 'font-medium text-amber-600 dark:text-amber-400';
  }
  return '';
});

// Subscription status helpers
const subscription = computed(() => props.institution.subscription);
const isDutyBased = computed(() => subscription.value?.is_duty_based ?? false);
const isFollowed = computed(() => subscription.value?.is_followed ?? false);
const isMuted = computed(() => subscription.value?.is_muted ?? false);

// Only show subscription actions for non-duty-based institutions (or when explicitly enabled)
const canShowSubscriptionActions = computed(() => {
  return props.showSubscriptionActions && !isDutyBased.value;
});

function formatDate(date: Date | string): string {
  const dateObj = typeof date === 'string' ? new Date(date) : date;
  return formatStaticTime(dateObj, { month: 'short', day: 'numeric' });
}
</script>
