<template>
  <!-- Compact view (read-only, space-efficient) -->
  <div v-if="compact" class="group flex items-center gap-2 py-1">
    <SmartLink :href="route('duties.edit', duty.id)" class="text-sm font-medium hover:underline shrink-0">
      {{ duty.name }}
    </SmartLink>
    <span class="text-muted-foreground/40">·</span>
    <div class="flex items-center gap-1.5 min-w-0 flex-1">
      <template v-if="duty.current_users && duty.current_users.length > 0">
        <template v-for="(user, idx) in duty.current_users" :key="user.id">
          <span v-if="idx > 0" class="text-muted-foreground/40">,</span>
          <SmartLink :href="route('users.edit', user.id)" class="text-sm text-muted-foreground hover:text-foreground truncate">
            {{ user.name }}
          </SmartLink>
        </template>
      </template>
      <template v-else-if="duty.previous_users?.[0]">
        <span class="text-sm text-muted-foreground/50 italic truncate">{{ duty.previous_users[0].name }}</span>
        <span class="text-[10px] text-muted-foreground/40">{{ $t('iki') }} {{ formatDate(duty.previous_users[0]?.pivot?.end_date) }}</span>
      </template>
      <span v-else class="text-sm text-muted-foreground/40">—</span>
    </div>
  </div>

  <!-- Edit view (full details, reorderable) -->
  <div v-else class="group py-2">
    <!-- Duty header row -->
    <div class="flex items-center justify-between gap-2">
      <SmartLink :href="route('duties.edit', duty.id)" class="flex items-center gap-1.5 hover:underline">
        <span class="font-medium text-foreground">{{ duty.name }}</span>
        <Pencil class="h-3 w-3 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
      </SmartLink>
      
      <!-- User count badge if multiple -->
      <span 
        v-if="duty.current_users && duty.current_users.length > 1" 
        class="text-xs text-muted-foreground"
      >
        {{ duty.current_users.length }} {{ $t('nariai') }}
      </span>
    </div>

    <!-- Users row -->
    <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 pl-0.5">
      <!-- Current users -->
      <template v-if="duty.current_users && duty.current_users.length > 0">
        <div 
          v-for="user in duty.current_users" 
          :key="user.id"
          class="flex items-center gap-1.5 text-sm group/user"
        >
          <div class="h-1.5 w-1.5 rounded-full bg-emerald-500 flex-shrink-0" />
          <SmartLink :href="route('users.edit', user.id)" class="hover:underline">
            <UserPopover :user="user" :size="20" show-name class="text-muted-foreground" />
          </SmartLink>
          <span class="text-[10px] text-muted-foreground/70">{{ formatDate(user.pivot?.start_date) }}</span>
          <SmartLink 
            v-if="user.pivot?.id"
            :href="route('dutiables.edit', user.pivot.id)"
            class="opacity-0 group-hover/user:opacity-100 transition-opacity"
          >
            <CalendarCog class="h-3 w-3 text-muted-foreground hover:text-foreground" />
          </SmartLink>
        </div>
      </template>

      <!-- Previous user fallback -->
      <template v-else-if="duty.previous_users && duty.previous_users.length > 0 && duty.previous_users[0]">
        <div class="flex items-center gap-1.5 text-sm opacity-50">
          <div class="h-1.5 w-1.5 rounded-full bg-zinc-400 flex-shrink-0" />
          <UserPopover :user="duty.previous_users[0]!" :size="20" show-name class="text-muted-foreground" />
          <span class="text-[10px] italic">{{ $t('iki') }} {{ formatDate(duty.previous_users[0]?.pivot?.end_date) }}</span>
        </div>
      </template>

      <!-- Empty state -->
      <span v-else class="text-xs text-muted-foreground/60 italic">—</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { CalendarCog, Pencil } from "lucide-vue-next";

import SmartLink from "../Public/SmartLink.vue";
import UserPopover from "../Avatars/UserPopover.vue";

// User with pivot data from duty relationship
type UserWithPivot = App.Entities.User & {
  pivot?: {
    id?: string;
    start_date?: string;
    end_date?: string | null;
  } | null;
};

defineProps<{
  duty: App.Entities.Duty & {
    current_users?: UserWithPivot[];
    previous_users?: UserWithPivot[];
  };
  institutionId: string;
  compact?: boolean;
}>();

const formatDate = (dateString?: string | null): string => {
  if (!dateString) return '—';
  const date = new Date(dateString);
  return date.toLocaleDateString('lt-LT', { 
    year: 'numeric', 
    month: '2-digit', 
    day: '2-digit' 
  });
};
</script>
