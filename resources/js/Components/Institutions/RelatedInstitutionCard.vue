<template>
  <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
    <!-- Institution info -->
    <Link :href="route('institutions.show', institution.id)" class="flex-1 min-w-0 flex items-center gap-3 group">
      <!-- Logo/Avatar -->
      <div class="relative shrink-0">
        <Avatar class="h-9 w-9 sm:h-10 sm:w-10 border border-zinc-200 dark:border-zinc-700">
          <AvatarFallback class="bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 text-xs font-medium">
            {{ initials }}
          </AvatarFallback>
        </Avatar>
        <!-- Subscription indicators -->
        <div v-if="followed || muted" class="absolute -bottom-1 -right-1 flex gap-0.5">
          <div v-if="followed && !muted" class="p-0.5 rounded-full bg-blue-500 text-white">
            <Eye class="h-2.5 w-2.5" />
          </div>
          <div v-if="muted" class="p-0.5 rounded-full bg-amber-500 text-white">
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

    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0 self-end sm:self-center">
      <InstitutionSubscriptionActions
        :followed
        :muted
        :follow-loading
        :mute-loading
        :duty-based
        @toggle-follow="$emit('toggle-follow', institution.id)"
        @toggle-mute="$emit('toggle-mute', institution.id)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { BellOff, Building2, Eye } from 'lucide-vue-next';

import InstitutionSubscriptionActions from './InstitutionSubscriptionActions.vue';

import type { AtstovavimasInstitution } from '@/Pages/Admin/Dashboard/types';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';

/**
 * Controlled, like its sibling InstitutionCompactCard: the list that renders these
 * owns subscription state, so both cards in a table stay in sync through one
 * `useInstitutionSubscription` instance.
 */
const props = defineProps<{
  institution: AtstovavimasInstitution;
  followed?: boolean;
  muted?: boolean;
  followLoading?: boolean;
  muteLoading?: boolean;
  dutyBased?: boolean;
}>();

defineEmits<{
  'toggle-follow': [institutionId: string];
  'toggle-mute': [institutionId: string];
}>();

const initials = computed(() => {
  const words = props.institution.name.split(' ').filter(word => word.length > 0);
  const [first, second] = words;

  if (first && second) {
    return (first[0]! + second[0]!).toUpperCase();
  }
  if (first) {
    return first.substring(0, 2).toUpperCase();
  }
  return 'IN';
});
</script>
