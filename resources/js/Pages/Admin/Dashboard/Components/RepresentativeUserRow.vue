<template>
  <div
    class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-white/60 p-2 transition-colors hover:bg-white/80 dark:border-zinc-700 dark:bg-zinc-800/50
      dark:hover:bg-zinc-700/50"
  >
    <!-- Avatar with activity indicator -->
    <div class="relative">
      <Avatar class="h-8 w-8">
        <AvatarImage v-if="user.profile_photo_path" :src="user.profile_photo_path" :alt="user.name" />
        <AvatarFallback class="text-xs bg-zinc-200 dark:bg-zinc-700">
          {{ getInitials(user.name) }}
        </AvatarFallback>
      </Avatar>
      <!-- Activity status dot -->
      <div
        class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white dark:border-zinc-800"
        :class="statusDotClasses"
        :title="statusLabel"
      />
    </div>

    <!-- User info -->
    <div class="flex-1 min-w-0">
      <div class="font-medium text-sm text-zinc-900 dark:text-zinc-100 truncate">
        {{ user.name }}
      </div>
      <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
        {{ user.duties && user.duties.length > 0 ? user.duties[0]?.institution_name : user.email }}
      </div>
    </div>

    <!-- Last activity -->
    <div class="text-right shrink-0">
      <div :class="['text-xs font-medium', lastActivityClasses]">
        {{ lastActivityText }}
      </div>
      <div v-if="user.duties.length > 1" class="text-xs text-zinc-400 dark:text-zinc-500">
        +{{ user.duties.length - 1 }} {{ $t('pareigos') }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { RepresentativeUser } from '../types';
import { getActivityDotClasses, getActivityTextClasses, getActivityLabel } from '../Composables/useActivityStatus';

import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { formatRelativeTime } from '@/Utils/IntlTime';

interface Props {
  user: RepresentativeUser;
}

const props = defineProps<Props>();

function getInitials(name: string): string {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
}

const statusDotClasses = computed(() => getActivityDotClasses(props.user.category));

const statusLabel = computed(() => getActivityLabel(props.user.category));

const lastActivityText = computed(() => {
  if (props.user.category === 'never' || !props.user.last_action) {
    return $t('Niekada');
  }
  return formatRelativeTime(new Date(props.user.last_action));
});

const lastActivityClasses = computed(() => getActivityTextClasses(props.user.category));
</script>
