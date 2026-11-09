<template>
  <Popover v-if="expandable && users.length">
    <PopoverTrigger as-child>
      <button type="button" data-slot="users-avatar-group" class="inline-flex min-w-0 items-center gap-2 px-1 py-1 text-sm text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground data-[state=open]:bg-secondary data-[state=open]:text-foreground pointer-coarse:min-h-11 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring" :aria-label="$t('Rodyti visus')">
        <span class="flex items-center">
          <UserAvatar v-for="(user, index) in visibleUsers" :key="user.id || index" :user :size="avatarSize" :class="index > 0 ? '-ml-2 [&_[data-slot=avatar]]:ring-2 [&_[data-slot=avatar]]:ring-background' : undefined" />
          <Avatar v-if="hasMoreUsers" :size="avatarSize" class="-ml-2 ring-2 ring-background"><AvatarFallback :class="textSizeClass">+{{ remainingCount }}</AvatarFallback></Avatar>
        </span>
        <ChevronDown class="size-3.5 shrink-0" aria-hidden="true" />
      </button>
    </PopoverTrigger>
    <PopoverContent class="w-[min(24rem,calc(100vw-2rem))] p-0" align="end" data-slot="users-avatar-group-popover">
      <p class="border-b border-border px-3 py-2 text-[11px] font-bold uppercase tracking-[0.18em] text-muted-foreground">{{ $t('Naudotojai') }} · {{ users.length }}</p>
      <ul class="max-h-72 divide-y divide-border/60 overflow-y-auto overscroll-contain">
        <li v-for="(user, index) in users" :key="user.id || user.name || index" class="flex min-h-11 items-center gap-2 px-3 py-1.5 hover:bg-secondary/40">
          <component :is="isClickable && user.id ? Link : 'div'" :href="isClickable && user.id ? route('users.show', user.id) : undefined" class="flex min-w-0 flex-1 items-center gap-2 hover:text-brand">
            <UserAvatar :user :size="popoverAvatarSize" />
            <span class="min-w-0"><span class="block truncate text-sm font-medium">{{ user.name }}</span><slot name="user-additional-info" :user /></span>
          </component>
        </li>
      </ul>
    </PopoverContent>
  </Popover>
  <div v-else data-slot="users-avatar-group" class="inline-flex flex-row items-center py-1" role="group" :aria-label="$t('Naudotojai')">
    <!-- Display avatars up to the maximum allowed -->
    <div v-for="(user, index) in visibleUsers" :key="user.id || index" class="relative flex items-center"
      :class="[avatarWrapperClass, index > 0 ? '-ml-2 [&_[data-slot=avatar]]:ring-2 [&_[data-slot=avatar]]:ring-background' : '']" :style="{ zIndex: visibleUsers.length - index }">
      <Link v-if="isClickable && user.id" :href="route('users.show', user.id)" class="contents">
        <UserPopover :user :size="avatarSize" :clickable="false">
          <template #additional-info>
            <slot name="user-additional-info" :user />
          </template>
        </UserPopover>
      </Link>
      <UserPopover v-else :user :size="avatarSize" :clickable="false">
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
              class="font-medium"
              :class="textSizeClass">
              +{{ remainingCount }}
            </AvatarFallback>
          </Avatar>
        </HoverCardTrigger>
        <HoverCardContent class="w-auto min-w-48 max-h-[280px] overflow-y-auto p-3">
          <div class="space-y-3">
            <h4 class="text-sm font-medium text-muted-foreground">
              {{ $t('Other users') }}
            </h4>
            <div class="grid gap-2">
              <template v-for="(user, index) in hiddenUsers" :key="user.id || user.name || index">
                <Link v-if="isClickable && user.id" :href="route('users.show', user.id)" class="contents">
                  <UserPopover show-name :size="popoverAvatarSize" :user :clickable="false">
                    <template #additional-info>
                      <slot name="user-additional-info" :user />
                    </template>
                  </UserPopover>
                </Link>
                <UserPopover v-else show-name :size="popoverAvatarSize" :user :clickable="false">
                  <template #additional-info>
                    <slot name="user-additional-info" :user />
                  </template>
                </UserPopover>
              </template>
            </div>
          </div>
        </HoverCardContent>
      </HoverCard>
    </div>

  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core';
import { ChevronDown } from '@lucide/vue';

import { useIsAdminContext } from '@/Composables/useIsAdminContext';
import { Avatar, AvatarFallback, avatarSizeClasses, mapPixelToSize, avatarTextSizes, type AvatarSize } from '@/Components/ui/avatar';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/Components/ui/hover-card';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

import type { AvatarUser } from './UserAvatar.vue';
import UserPopover from './UserPopover.vue';
import UserAvatar from './UserAvatar.vue';

const props = withDefaults(defineProps<{
  users: AvatarUser[];
  max?: number;
  size?: number | AvatarSize;
  limitByScreen?: boolean;
  /** Render a trailing chevron that opens a dropdown listing all users. */
  expandable?: boolean;
  /** Wrap each avatar in a link to the user's show page (requires user.id).
   *  Defaults to true when rendered inside `/mano/*` and the user has view permission. */
  clickable?: boolean;
}>(), {
  clickable: undefined,
});

const page = usePage();
const isAdminContext = useIsAdminContext();

const canViewUserProfile = computed(() => {
  return !!page.props.auth?.can?.['users.read.padalinys'];
});

const isClickable = computed(() => {
  if (props.clickable === false) {
    return false;
  }

  if (props.clickable === true) {
    return true;
  }

  return isAdminContext.value && canViewUserProfile.value;
});

const breakpoints = useBreakpoints(breakpointsTailwind);
const isSmallerThanSm = breakpoints.smaller('sm');
const isSmallerThanMd = breakpoints.smaller('md');

// Compute the maximum number of users to display, taking into account screen size if limitByScreen is true
const maxVisibleUsers = computed(() => {
  const defaultMax = props.max ?? 4;

  if (!props.limitByScreen) return defaultMax;

  if (isSmallerThanSm.value) return Math.min(defaultMax, 2);
  if (isSmallerThanMd.value) return Math.min(defaultMax, 3);

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

const popoverAvatarSize = computed<AvatarSize>(() => {
  const sizeOrder: AvatarSize[] = ['xxs', 'xs', 'sm', 'default', 'lg', 'xl'];
  const currentIndex = sizeOrder.indexOf(avatarSize.value);
  // Popup rows stay at least 24px to keep names readable.
  return sizeOrder[Math.max(1, currentIndex - 1)];
});
</script>
