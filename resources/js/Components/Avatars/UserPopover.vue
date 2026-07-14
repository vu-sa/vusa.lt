<template>
  <HoverCard :open-delay="300" :close-delay="100">
    <HoverCardTrigger as-child>
      <template v-if="!showName">
        <Link
          v-if="shouldRenderLink"
          :href="profileUrl"
          v-bind="$attrs"
          class="inline-flex items-center leading-none"
          :class="avatarWrapperClass"
        >
          <UserAvatar
            :user
            :size="avatarSize"
            :interactive="true"
          />
        </Link>
        <div
          v-else
          v-bind="$attrs"
          class="inline-flex items-center leading-none"
          :class="avatarWrapperClass"
        >
          <UserAvatar
            :user
            :size="avatarSize"
            :interactive="true"
          />
        </div>
      </template>
      <template v-else>
        <Link
          v-if="shouldRenderLink"
          :href="profileUrl"
          v-bind="$attrs"
          class="inline-flex items-center gap-2 px-1 py-0.5 rounded-md transition-colors hover:bg-accent group"
        >
          <UserAvatar :user :size="avatarSize" :interactive="true" />
          <span :class="[nameTextClass, 'font-medium group-hover:text-accent-foreground']">
            {{ user.name }}
          </span>
        </Link>
        <div
          v-else
          v-bind="$attrs"
          class="inline-flex items-center gap-2 px-1 py-0.5 rounded-md transition-colors hover:bg-accent group"
        >
          <UserAvatar :user :size="avatarSize" :interactive="true" />
          <span :class="[nameTextClass, 'font-medium group-hover:text-accent-foreground']">
            {{ user.name }}
          </span>
        </div>
      </template>
    </HoverCardTrigger>

    <HoverCardContent class="w-64 overflow-hidden p-0 shadow-lg">
      <!-- User photo / initials fallback -->
      <div class="relative aspect-[4/3] w-full overflow-hidden">
        <img
          v-if="photo && !imageError"
          class="h-full w-full object-cover"
          :src="photo"
          :alt="user.name"
          loading="lazy"
          :style="{ objectPosition: focalPoint }"
          @error="imageError = true"
        >
        <div
          v-else
          class="flex h-full w-full items-center justify-center bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-700 dark:to-zinc-800"
        >
          <span class="text-2xl font-bold text-zinc-400 dark:text-zinc-500">{{ initials }}</span>
        </div>
      </div>

      <!-- User information -->
      <div class="flex flex-col gap-2 p-3">
        <h3 class="truncate text-sm font-semibold leading-tight">
          {{ user.name }}
          <span v-if="user.show_pronouns && pronounsLabel" class="text-[10px] font-normal text-muted-foreground">
            ({{ pronounsLabel }})
          </span>
        </h3>

        <!-- Contact information -->
        <div class="space-y-1.5 text-xs">
          <a
            v-if="user.email"
            class="flex items-center gap-2 text-muted-foreground transition-colors hover:text-foreground"
            :href="`mailto:${user.email}`"
          >
            <IFluentMail20Regular width="13" height="13" class="shrink-0" />
            <span class="line-clamp-1 hover:underline">{{ user.email }}</span>
          </a>
          <a
            v-if="user.phone"
            class="flex items-center gap-2 text-muted-foreground transition-colors hover:text-foreground"
            :href="`tel:${user.phone}`"
          >
            <IFluentPhone20Regular width="13" height="13" class="shrink-0" />
            <span class="line-clamp-1 hover:underline">{{ user.phone }}</span>
          </a>
          <a
            v-if="user.facebook_url"
            class="flex items-center gap-2 text-muted-foreground transition-colors hover:text-foreground"
            :href="user.facebook_url"
            target="_blank"
            rel="noopener noreferrer"
          >
            <ISimpleIconsFacebook width="13" height="13" class="shrink-0" />
            <span class="line-clamp-1 hover:underline">Facebook</span>
          </a>
        </div>

        <!-- Additional information slot -->
        <slot name="additional-info" />
      </div>
    </HoverCardContent>
  </HoverCard>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

import UserAvatar from './UserAvatar.vue';

import { useIsAdminContext } from '@/Composables/useIsAdminContext';
import ISimpleIconsFacebook from '~icons/simple-icons/facebook';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/Components/ui/hover-card';
import { avatarSizeClasses, mapPixelToSize, type AvatarSize } from '@/Components/ui/avatar';

const props = withDefaults(defineProps<{
  showName?: boolean;
  size?: number | AvatarSize;
  user: Record<string, any>;
  /** Whether the avatar/name should link to the admin user profile page.
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

const shouldRenderLink = computed(() => {
  if (props.clickable === false) {
    return false;
  }

  if (props.clickable === true) {
    return !!props.user?.id;
  }

  return isAdminContext.value && !!props.user?.id && canViewUserProfile.value;
});

const profileUrl = computed(() => {
  return route('users.show', props.user.id);
});

// Support both pixel values (backward compat) and size variant names
const avatarSize = computed<AvatarSize>(() => {
  if (typeof props.size === 'string') {
    return props.size as AvatarSize;
  }
  return mapPixelToSize(props.size);
});

// Text size for the name label when showName is true
const nameTextClass = computed(() => {
  const sizeMap: Record<AvatarSize, string> = {
    xs: 'text-xs',
    sm: 'text-sm',
    default: 'text-base',
    lg: 'text-base',
    xl: 'text-lg',
  };
  return sizeMap[avatarSize.value];
});

const avatarWrapperClass = computed(() => {
  return avatarSizeClasses[avatarSize.value];
});

const photo = computed(() => {
  if (props.user.src) return props.user.src;
  if (props.user.profile_photo_path) return props.user.profile_photo_path;
  return null;
});

// Fall back to initials when the photo path is set but fails to load (broken image).
const imageError = ref(false);
watch(() => photo.value, () => {
  imageError.value = false;
});

// Keep the face in frame for the taller photo (mirrors ContactWithPhoto.vue).
const focalPoint = computed(() => props.user.profile_photo_focal_point ?? '50% 30%');

const initials = computed(() => {
  const { name } = props.user;
  if (!name || typeof name !== 'string') return '';
  const words = name.trim().split(/\s+/);
  if (words.length === 1) return words[0].substring(0, 2).toUpperCase();
  return (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
});

const pronounsLabel = computed(() => {
  const p = props.user.pronouns;
  if (Array.isArray(p)) return p.filter(Boolean).join('/');
  return typeof p === 'string' ? p : '';
});
</script>
