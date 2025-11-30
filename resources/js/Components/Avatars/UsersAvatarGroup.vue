<template>
  <div class="inline-flex flex-row items-center p-1" role="group" aria-label="Group of users">
    <!-- Display avatars up to the maximum allowed -->
    <div v-for="(user, index) in visibleUsers" :key="user.id || index" class="relative group/avatar"
      :class="{ '-ml-2': index > 0 }" :style="{ zIndex: visibleUsers.length - index }">
      <UserPopover :user :size>
        <template #additional-info>
          <slot name="user-additional-info" :user />
        </template>
      </UserPopover>
    </div>

    <!-- If there are more users than the maximum, show a count avatar -->
    <HoverCard v-if="hasMoreUsers">
      <HoverCardTrigger>
        <div class="-ml-2 relative group/more" :style="{ zIndex: 0 }">
          <Avatar :class="[
            avatarSizeClass,
            'transition-transform duration-200 group-hover/more:scale-110 group-hover/more:ring-2 group-hover/more:ring-primary/40 cursor-pointer border-2 border-background'
          ]">
            <AvatarFallback
              class="bg-primary/10 text-primary-foreground font-medium hover:bg-primary/20 transition-colors"
              :class="textSizeClass">
              +{{ remainingCount }}
            </AvatarFallback>
          </Avatar>
        </div>
      </HoverCardTrigger>
      <HoverCardContent class="p-3 w-auto min-w-48 max-h-[280px] overflow-y-auto">
        <div class="space-y-3">
          <h4 class="text-sm font-medium text-muted-foreground">
            {{ $t('Other users') }}
          </h4>
          <div class="grid gap-2">
            <UserPopover v-for="user in hiddenUsers" :key="user.id || user.name" show-name :size="avatarSizeForPopover"
              :user>
              <template #additional-info>
                <slot name="user-additional-info" :user />
              </template>
            </UserPopover>
          </div>
        </div>
      </HoverCardContent>
    </HoverCard>

    <!-- Named slot for additional actions -->
    <slot name="actions" />
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

import UserPopover from "./UserPopover.vue";

import { Avatar, AvatarFallback } from "@/Components/ui/avatar";
import { HoverCard, HoverCardContent, HoverCardTrigger } from "@/Components/ui/hover-card";

const props = defineProps<{
  users: App.Entities.User[];
  max?: number;
  size?: number;
  limitByScreen?: boolean;
}>();

// Compute the maximum number of users to display, taking into account screen size if limitByScreen is true
const maxVisibleUsers = computed(() => {
  const defaultMax = props.max ?? 4;

  if (!props.limitByScreen) return defaultMax;

  // Check if the screen is small and adjust the max visible users accordingly
  if (typeof window !== 'undefined') {
    if (window.innerWidth < 640) return Math.min(defaultMax, 2);  // sm
    if (window.innerWidth < 768) return Math.min(defaultMax, 3);  // md
  }

  return defaultMax;
});

// Users that will be visible in the avatar group
const visibleUsers = computed(() => {
  return props.users.slice(0, maxVisibleUsers.value);
});

// Users that will be hidden in the popover
const hiddenUsers = computed(() => {
  return props.users.slice(maxVisibleUsers.value);
});

// Check if there are more users than the maximum
const hasMoreUsers = computed(() => {
  return props.users.length > maxVisibleUsers.value;
});

// Calculate the remaining count for the +X avatar
const remainingCount = computed(() => {
  return props.users.length - maxVisibleUsers.value;
});

// Avatar size class
const avatarSizeClass = computed(() => {
  const sizeValue = props.size ?? 40;
  return `h-[${sizeValue}px] w-[${sizeValue}px]`;
});

// Text size for the +X indicator
const textSizeClass = computed(() => {
  const size = props.size || 40;
  if (size < 24) return 'text-xs';
  if (size < 32) return 'text-sm';
  if (size < 48) return 'text-base';
  return 'text-lg';
});

// Use a slightly smaller avatar size in the popup list for better UX
const avatarSizeForPopover = computed(() => {
  const size = props.size || 40;
  return Math.max(24, size - 8);
});
</script>
