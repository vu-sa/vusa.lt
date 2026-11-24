<template>
  <Popover v-if="expandable && (users.length || showWhenEmpty)" v-model:open="open">
    <PopoverTrigger as-child>
      <button
        type="button"
        :data-slot="triggerSlot"
        class="inline-flex min-w-0 shrink-0 items-center gap-2 px-1 py-1 text-left text-sm text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground data-[state=open]:bg-secondary data-[state=open]:text-foreground pointer-coarse:min-h-11 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
        :aria-label="triggerLabel ?? $t('Rodyti visus')"
      >
        <slot name="trigger-prefix" />
        <span v-if="users.length" :class="['items-center', hideAvatarsOnMobile ? 'hidden sm:flex' : 'flex']">
          <UserAvatar
            v-for="(user, index) in visibleUsers"
            :key="user.id || index"
            :user
            :size="avatarSize"
            :class="index > 0 ? '-ml-2 [&_[data-slot=avatar]]:ring-2 [&_[data-slot=avatar]]:ring-background' : undefined"
          />
          <Avatar v-if="hasMoreUsers" :size="avatarSize" class="-ml-2 ring-2 ring-background">
            <AvatarFallback :class="textSizeClass">
              +{{ remainingCount }}
            </AvatarFallback>
          </Avatar>
        </span>
        <slot v-else name="trigger-empty" />
        <slot name="trigger-suffix" />
        <ChevronDown class="size-3.5 shrink-0" aria-hidden="true" />
      </button>
    </PopoverTrigger>
    <PopoverContent class="w-[min(24rem,calc(100vw-2rem))] p-0" align="end" :data-slot="popoverSlot">
      <p class="border-b border-border px-3 py-2 text-[11px] font-bold uppercase tracking-[0.18em] text-muted-foreground">
        {{ popoverTitle ?? $t('Naudotojai') }} · {{ users.length }}
      </p>
      <ul v-if="users.length" class="max-h-72 divide-y divide-border/60 overflow-y-auto overscroll-contain">
        <li
          v-for="(user, index) in users"
          :key="user.id || user.name || index"
          :class="['flex min-h-11 items-center gap-2 py-1.5 hover:bg-secondary/40', $slots['user-actions'] ? 'pr-1 pl-3' : 'px-3']"
        >
          <component
            :is="hrefFor(user) ? Link : 'div'"
            :href="hrefFor(user)"
            :class="['flex min-w-0 flex-1 items-center gap-2', hrefFor(user) && 'hover:text-brand']"
          >
            <UserAvatar :user :size="popoverAvatarSize" />
            <span class="min-w-0">
              <span class="block truncate text-sm font-medium">{{ user.name }}</span>
              <slot name="user-additional-info" :user />
            </span>
          </component>
          <slot name="user-actions" :user />
        </li>
      </ul>
      <slot v-else name="popover-empty" />
      <slot name="popover-footer" />
    </PopoverContent>
  </Popover>

  <div v-else data-slot="users-avatar-group" class="inline-flex flex-row items-center py-1" role="group" :aria-label="$t('Naudotojai')">
    <div
      v-for="(user, index) in visibleUsers"
      :key="user.id || index"
      class="relative flex items-center"
      :class="[avatarWrapperClass, index > 0 ? '-ml-2 [&_[data-slot=avatar]]:ring-2 [&_[data-slot=avatar]]:ring-background' : '']"
      :style="{ zIndex: visibleUsers.length - index }"
    >
      <UserPopover :user :size="avatarSize" :clickable="clickable === false ? false : undefined">
        <template v-if="$slots['user-additional-info']" #additional-info>
          <slot name="user-additional-info" :user />
        </template>
      </UserPopover>
    </div>

    <div v-if="hasMoreUsers" class="relative -ml-2 flex items-center" :class="avatarWrapperClass" :style="{ zIndex: 0 }">
      <Popover v-model:open="open">
        <PopoverTrigger as-child>
          <button
            type="button"
            data-slot="users-avatar-group-overflow"
            class="inline-flex items-center leading-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
            :aria-label="$t('Other users')"
          >
            <Avatar :size="avatarSize" interactive class="ring-2 ring-background">
              <AvatarFallback class="font-medium" :class="textSizeClass">
                +{{ remainingCount }}
              </AvatarFallback>
            </Avatar>
          </button>
        </PopoverTrigger>
        <PopoverContent class="w-[min(24rem,calc(100vw-2rem))] p-0" align="end" :data-slot="popoverSlot">
          <p class="border-b border-border px-3 py-2 text-[11px] font-bold uppercase tracking-[0.18em] text-muted-foreground">
            {{ popoverTitle ?? $t('Other users') }} · {{ hiddenUsers.length }}
          </p>
          <ul class="max-h-72 divide-y divide-border/60 overflow-y-auto overscroll-contain">
            <li
              v-for="(user, index) in hiddenUsers"
              :key="user.id || user.name || index"
              class="flex min-h-11 items-center gap-2 px-3 py-1.5 hover:bg-secondary/40"
            >
              <component
                :is="hrefFor(user) ? Link : 'div'"
                :href="hrefFor(user)"
                :class="['flex min-w-0 flex-1 items-center gap-2', hrefFor(user) && 'hover:text-brand']"
              >
                <UserAvatar :user :size="popoverAvatarSize" />
                <span class="min-w-0">
                  <span class="block truncate text-sm font-medium">{{ user.name }}</span>
                  <slot name="user-additional-info" :user />
                </span>
              </component>
            </li>
          </ul>
        </PopoverContent>
      </Popover>
    </div>
  </div>
</template>

<script setup lang="ts" generic="T extends AvatarUser = AvatarUser">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core';
import { ChevronDown } from '@lucide/vue';

import { Avatar, AvatarFallback, avatarSizeClasses, mapPixelToSize, avatarTextSizes, type AvatarSize } from '@/Components/ui/avatar';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

import type { AvatarUser } from './UserAvatar.vue';
import UserPopover from './UserPopover.vue';
import UserAvatar from './UserAvatar.vue';
import { useUserLink } from './useUserLink';

const props = withDefaults(defineProps<{
  users: T[];
  max?: number;
  size?: number | AvatarSize;
  limitByScreen?: boolean;
  /** Render the whole group as a click trigger listing every user. */
  expandable?: boolean;
  /** Explicit opt-out from user profile linking; cannot force links. */
  clickable?: boolean;
  showWhenEmpty?: boolean;
  hideAvatarsOnMobile?: boolean;
  popoverTitle?: string;
  triggerLabel?: string;
  triggerSlot?: string;
  popoverSlot?: string;
}>(), {
  clickable: undefined,
  triggerSlot: 'users-avatar-group',
  popoverSlot: 'users-avatar-group-popover',
});

defineSlots<{
  'trigger-prefix'?: () => unknown;
  'trigger-empty'?: () => unknown;
  'trigger-suffix'?: () => unknown;
  'user-additional-info'?: (props: { user: T }) => unknown;
  'user-actions'?: (props: { user: T }) => unknown;
  'popover-empty'?: () => unknown;
  'popover-footer'?: () => unknown;
}>();

const open = defineModel<boolean>('open', { default: false });

const { hrefFor } = useUserLink(undefined, { disabled: () => props.clickable === false });

const breakpoints = useBreakpoints(breakpointsTailwind);
const isSmallerThanSm = breakpoints.smaller('sm');
const isSmallerThanMd = breakpoints.smaller('md');

const maxVisibleUsers = computed(() => {
  const defaultMax = props.max ?? 4;

  if (!props.limitByScreen) return defaultMax;

  if (isSmallerThanSm.value) return Math.min(defaultMax, 2);
  if (isSmallerThanMd.value) return Math.min(defaultMax, 3);

  return defaultMax;
});

const visibleUsers = computed(() => props.users.slice(0, maxVisibleUsers.value));
const hiddenUsers = computed(() => props.users.slice(maxVisibleUsers.value));
const hasMoreUsers = computed(() => props.users.length > maxVisibleUsers.value);
const remainingCount = computed(() => props.users.length - maxVisibleUsers.value);

const avatarSize = computed<AvatarSize>(() => {
  if (typeof props.size === 'string') {
    return props.size as AvatarSize;
  }
  return mapPixelToSize(props.size);
});

const textSizeClass = computed(() => avatarTextSizes[avatarSize.value]);
const avatarWrapperClass = computed(() => avatarSizeClasses[avatarSize.value]);

const popoverAvatarSize = computed<AvatarSize>(() => {
  const sizeOrder: AvatarSize[] = ['xxs', 'xs', 'sm', 'default', 'lg', 'xl'];
  const currentIndex = sizeOrder.indexOf(avatarSize.value);
  // Popup rows stay at least 24px to keep names readable.
  return sizeOrder[Math.max(1, currentIndex - 1)] ?? 'xs';
});
</script>
