<template>
  <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
    <!-- Institution info -->
    <Link :href="route('institutions.show', institution.id)" class="flex-1 min-w-0 flex items-center gap-3 group">
      <!-- Logo/Avatar -->
      <div class="relative shrink-0">
        <Avatar class="h-9 w-9 sm:h-10 sm:w-10 border border-zinc-200 dark:border-zinc-700">
          <AvatarFallback class="bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 text-xs font-medium">
            {{ getInitials(institution.name) }}
          </AvatarFallback>
        </Avatar>
        <!-- Subscription indicators -->
        <div v-if="isFollowed || isMuted" class="absolute -bottom-1 -right-1 flex gap-0.5">
          <div v-if="isFollowed && !isMuted" class="p-0.5 rounded-full bg-blue-500 text-white">
            <Eye class="h-2.5 w-2.5" />
          </div>
          <div v-if="isMuted" class="p-0.5 rounded-full bg-amber-500 text-white">
            <BellOff class="h-2.5 w-2.5" />
          </div>
        </div>
      </div>

      <!-- Text content -->
      <div class="flex-1 min-w-0">
        <p class="font-medium text-sm sm:text-base text-zinc-900 dark:text-zinc-100 line-clamp-2 sm:truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
          {{ institution.name }}
        </p>
        <div v-if="institution.tenant?.shortname" class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
          <span class="flex items-center gap-1">
            <Building2 class="h-3 w-3" />
            {{ institution.tenant.shortname }}
          </span>
        </div>
      </div>
    </Link>

    <!-- Subscription actions -->
    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0 self-end sm:self-center">
      <TooltipProvider>
        <!-- Follow/Unfollow button -->
        <Tooltip>
          <TooltipTrigger as-child>
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              :disabled="isFollowLoading"
              @click="toggleFollow"
            >
              <Loader2 v-if="isFollowLoading" class="h-4 w-4 animate-spin" />
              <Eye v-else-if="!isFollowed" class="h-4 w-4 text-zinc-400 hover:text-blue-500" />
              <EyeOff v-else class="h-4 w-4 text-blue-500" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>
            {{ isFollowed ? $t('Nebesekti') : $t('Sekti') }}
          </TooltipContent>
        </Tooltip>

        <!-- Mute/Unmute button (only if followed) -->
        <Tooltip v-if="isFollowed">
          <TooltipTrigger as-child>
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              :disabled="isMuteLoading"
              @click="toggleMute"
            >
              <Loader2 v-if="isMuteLoading" class="h-4 w-4 animate-spin" />
              <BellOff v-else-if="isMuted" class="h-4 w-4 text-amber-500" />
              <Bell v-else class="h-4 w-4 text-zinc-400 hover:text-amber-500" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>
            {{ isMuted ? $t('Įjungti pranešimus') : $t('Nutildyti') }}
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, EyeOff, Bell, BellOff, Building2, Loader2 } from 'lucide-vue-next';

import type { AtstovavimosInstitution } from '../types';
import { useInstitutionSubscription } from '../Composables/useInstitutionSubscription';

import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

const props = defineProps<{
  institution: AtstovavimosInstitution;
}>();

// Subscription state
const isFollowed = ref(props.institution.subscription?.is_followed ?? false);
const isMuted = ref(props.institution.subscription?.is_muted ?? false);

const subscriptionState = computed(() => ({
  is_followed: isFollowed.value,
  is_muted: isMuted.value,
  is_duty_based: props.institution.subscription?.is_duty_based ?? false,
}));

// Use the subscription composable
const { toggleFollow: doToggleFollow, toggleMute: doToggleMute, followLoading, muteLoading } = useInstitutionSubscription();

const isFollowLoading = computed(() => followLoading.value[props.institution.id] ?? false);
const isMuteLoading = computed(() => muteLoading.value[props.institution.id] ?? false);

const toggleFollow = async () => {
  const newState = await doToggleFollow(props.institution.id, subscriptionState.value);
  isFollowed.value = newState;
  // If unfollowed, also unmute locally
  if (!newState) {
    isMuted.value = false;
  }
};

const toggleMute = async () => {
  const newState = await doToggleMute(props.institution.id, subscriptionState.value);
  isMuted.value = newState;
};

const getInitials = (name: string) => {
  const words = name.split(' ').filter(word => word.length > 0);
  if (words.length >= 2) {
    const first = words[0];
    const second = words[1];
    if (first && second && first[0] && second[0]) {
      return (first[0] + second[0]).toUpperCase();
    }
  }
  if (words.length === 1) {
    const first = words[0];
    if (first) {
      return first.substring(0, 2).toUpperCase();
    }
  }
  return 'IN';
};
</script>
