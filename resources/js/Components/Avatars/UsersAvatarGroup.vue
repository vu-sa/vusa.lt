<template>
  <div class="inline-flex flex-row items-center p-1" role="group" aria-label="Group of users">
    <!-- Display avatars up to the maximum allowed -->
    <div v-for="(user, index) in visibleUsers" :key="user.id || index" class="relative flex items-center"
      :class="[avatarWrapperClass, { '-ml-2': index > 0 }]" :style="{ zIndex: visibleUsers.length - index }">
      <UserPopover :user :size="avatarSize">
        <template #additional-info>
          <slot name="user-additional-info" :user />
        </template>
      </UserPopover>
    </div>

    <!-- If there are more users than the maximum, show a count avatar -->
    <div v-if="hasMoreUsers" class="-ml-2 relative flex items-center" :class="avatarWrapperClass" :style="{ zIndex: 0 }">
      <HoverCard>
        <HoverCardTrigger as-child>
          <Avatar 
            :size="avatarSize" 
            :interactive="true"
          >
            <AvatarFallback
              class="text-foreground font-medium"
              :class="textSizeClass">
              +{{ remainingCount }}
            </AvatarFallback>
          </Avatar>
        </HoverCardTrigger>
        <HoverCardContent class="p-3 w-auto min-w-48 max-h-[280px] overflow-y-auto">
        <div class="space-y-3">
          <h4 class="text-sm font-medium text-muted-foreground">
            {{ $t('Other users') }}
          </h4>
          <div class="grid gap-2">
            <UserPopover v-for="user in hiddenUsers" :key="user.id || user.name" show-name :size="popoverAvatarSize"
              :user>
              <template #additional-info>
                <slot name="user-additional-info" :user />
              </template>
            </UserPopover>
          </div>
        </div>
      </HoverCardContent>
    </HoverCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

import UserPopover from "./UserPopover.vue";

import { Avatar, AvatarFallback, avatarSizeClasses, mapPixelToSize, avatarTextSizes, type AvatarSize } from "@/Components/ui/avatar";
import { HoverCard, HoverCardContent, HoverCardTrigger } from "@/Components/ui/hover-card";

const props = defineProps<{
  users: App.Entities.User[];
  max?: number;
  size?: number | AvatarSize;
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

// Support both pixel values (backward compat) and size variant names
const avatarSize = computed<AvatarSize>(() => {
  if (typeof props.size === 'string') {
    return props.size as AvatarSize;
  }
  return mapPixelToSize(props.size);
});

// Text size for the +X indicator
const textSizeClass = computed(() => {
  return avatarTextSizes[avatarSize.value];
});

const avatarWrapperClass = computed(() => {
  return avatarSizeClasses[avatarSize.value];
});

// Use a slightly smaller avatar size in the popup list for better UX
const popoverAvatarSize = computed<AvatarSize>(() => {
  const sizeOrder: AvatarSize[] = ['xs', 'sm', 'default', 'lg', 'xl'];
  const currentIndex = sizeOrder.indexOf(avatarSize.value);
  // Go one size smaller, but don't go below 'xs'
  return sizeOrder[Math.max(0, currentIndex - 1)];
});
</script>
