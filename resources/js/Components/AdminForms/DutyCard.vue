<template>
  <div class="py-3" data-slot="duty-card">
    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
      <Link :href="route('duties.show', duty.id)" class="font-medium text-foreground hover:text-brand hover:underline">
        {{ duty.name }}
      </Link>
      <span class="flex items-center gap-3 text-xs text-muted-foreground">
        <span v-if="duty.places_to_occupy" class="tabular-nums">
          {{ (duty.current_users?.length ?? 0) }} / {{ duty.places_to_occupy }}
        </span>
        <Button
          v-if="canManage"
          type="button"
          variant="ghost"
          size="sm"
          class="h-7 gap-1 px-2 text-xs pointer-coarse:h-11"
          @click="$emit('assign', duty)"
        >
          <UserPlus class="size-3.5" aria-hidden="true" />
          {{ $t('Pridėti asmenį') }}
        </Button>
      </span>
    </div>

    <ul v-if="!compact || duty.current_users?.length" class="mt-1.5 flex flex-col gap-1">
      <li
        v-for="user in duty.current_users ?? []"
        :key="user.id"
        class="flex items-center gap-2 text-sm"
      >
        <Link :href="route('users.show', user.id)" class="min-w-0 hover:underline">
          <UserPopover :user :size="20" show-name :clickable="false" class="text-muted-foreground" />
        </Link>
        <span class="text-xs text-muted-foreground tabular-nums">{{ $t('nuo') }} {{ formatDate(user.pivot?.start_date) }}</span>
        <Button
          v-if="canManage && user.pivot?.id"
          type="button"
          variant="ghost"
          size="sm"
          class="size-7 p-0 pointer-coarse:size-11"
          :aria-label="$t('Redaguoti kadenciją')"
          @click="$emit('edit-term', duty, user)"
        >
          <CalendarCog class="size-3.5 text-muted-foreground" aria-hidden="true" />
        </Button>
      </li>

      <li
        v-if="!duty.current_users?.length && duty.previous_users?.[0]"
        class="flex items-center gap-2 text-sm text-muted-foreground"
      >
        <UserPopover :user="duty.previous_users[0]" :size="20" show-name />
        <span class="text-xs tabular-nums">{{ $t('iki') }} {{ formatDate(duty.previous_users[0]?.pivot?.end_date) }}</span>
      </li>

      <li v-else-if="!duty.current_users?.length" class="text-xs text-status-attention">
        {{ $t('Neužimta') }}
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarCog, UserPlus } from 'lucide-vue-next';

import UserPopover from '../Avatars/UserPopover.vue';

import { Button } from '@/Components/ui/button';
import { formatDate as formatIsoDate } from '@/Utils/dateTime';

// User with pivot data from the duty relationship.
export type UserWithPivot = App.Entities.User & {
  pivot?: {
    id?: string;
    start_date?: string;
    end_date?: string | null;
  } | null;
};

export type DutyWithUsers = App.Entities.Duty & {
  current_users?: UserWithPivot[];
  previous_users?: UserWithPivot[];
};

defineProps<{
  duty: DutyWithUsers;
  /** Read-only rows hide the assign and edit-term controls. */
  canManage?: boolean;
  /** Hides the members line of a vacant duty's history; the list is scan-only. */
  compact?: boolean;
}>();

defineEmits<{
  'assign': [duty: DutyWithUsers];
  'edit-term': [duty: DutyWithUsers, user: UserWithPivot];
}>();

const formatDate = (value?: string | null): string => (value ? formatIsoDate(value) : '—');
</script>
