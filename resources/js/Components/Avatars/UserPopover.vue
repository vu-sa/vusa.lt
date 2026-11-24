<template>
  <HoverCard :open-delay="300" :close-delay="100" @update:open="loadPhoto">
    <HoverCardTrigger as-child>
      <template v-if="!showName">
        <Link
          v-if="shouldRenderLink && profileUrl"
          :href="profileUrl"
          v-bind="$attrs"
          class="inline-flex items-center leading-none"
          :class="avatarWrapperClass"
        >
          <UserAvatar
            :user
            :size="avatarSize"
            interactive
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
            interactive
          />
        </div>
      </template>
      <template v-else>
        <Link
          v-if="shouldRenderLink && profileUrl"
          :href="profileUrl"
          v-bind="$attrs"
          class="group inline-flex items-center gap-2 px-1 py-0.5 transition-colors hover:bg-accent"
        >
          <UserAvatar :user :size="avatarSize" interactive />
          <span :class="[nameTextClass, 'font-medium group-hover:text-accent-foreground']">
            {{ user.name }}
          </span>
        </Link>
        <div
          v-else
          v-bind="$attrs"
          class="group inline-flex items-center gap-2 px-1 py-0.5 transition-colors hover:bg-accent"
        >
          <UserAvatar :user :size="avatarSize" interactive />
          <span :class="[nameTextClass, 'font-medium group-hover:text-accent-foreground']">
            {{ user.name }}
          </span>
        </div>
      </template>
    </HoverCardTrigger>

    <HoverCardContent class="w-64 overflow-hidden p-0">
      <!-- User photo / initials fallback -->
      <div class="relative aspect-[4/3] w-full overflow-hidden">
        <MediaImage
          v-if="photo && !imageError"
          :image="preview?.image"
          sizes="256px"
          class="h-full w-full object-cover"
          :src="photo"
          :alt="user.name ?? ''"
          loading="lazy"
          :focal-point
          @error="imageError = true" />
        <div
          v-else
          class="flex h-full w-full items-center justify-center bg-muted"
        >
          <span class="text-2xl font-bold text-muted-foreground">{{ initials(user.name) }}</span>
        </div>
      </div>

      <!-- User information -->
      <div class="flex flex-col gap-2 p-3">
        <h3 class="truncate text-sm font-semibold leading-tight">
          {{ user.name }}
          <span v-if="user.show_pronouns && pronounsLabel" class="text-xs font-normal text-muted-foreground">
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
            <Mail class="size-3.5 shrink-0" />
            <span class="line-clamp-1 hover:underline">{{ user.email }}</span>
          </a>
          <a
            v-if="user.phone"
            class="flex items-center gap-2 text-muted-foreground transition-colors hover:text-foreground"
            :href="`tel:${user.phone}`"
          >
            <Phone class="size-3.5 shrink-0" />
            <span class="line-clamp-1 hover:underline">{{ user.phone }}</span>
          </a>
          <a
            v-if="user.facebook_url"
            class="flex items-center gap-2 text-muted-foreground transition-colors hover:text-foreground"
            :href="safeHref(user.facebook_url)"
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
import { Link } from '@inertiajs/vue3';
import { Mail, Phone } from '@lucide/vue';

import MediaImage from '@/Components/Brand/MediaImage.vue';
import { useApi } from '@/Composables/useApi';
import type { ImageData } from '@/Types/media';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/Components/ui/hover-card';
import { avatarSizeClasses, mapPixelToSize, type AvatarSize } from '@/Components/ui/avatar';
import { safeHref } from '@/Utils/safeHref';
import { initials } from '@/Utils/String';

import UserAvatar, { type AvatarUser } from './UserAvatar.vue';
import { useUserLink } from './useUserLink';

import ISimpleIconsFacebook from '~icons/simple-icons/facebook';

defineOptions({ inheritAttrs: false });

export interface PopoverUser extends AvatarUser {
  email?: string | null;
  phone?: string | null;
  facebook_url?: string | null;
  src?: string | null;
  show_pronouns?: boolean | null;
  pronouns?: unknown;
}

const props = withDefaults(defineProps<{
  showName?: boolean;
  size?: number | AvatarSize;
  user: PopoverUser;
  /** Explicit opt-out from user profile linking; cannot force links. */
  clickable?: boolean;
}>(), {
  clickable: undefined,
});

const { canLink: shouldRenderLink, canLinkUsers, href: profileUrl } = useUserLink(
  () => props.user,
  { disabled: () => props.clickable === false },
);

const avatarSize = computed<AvatarSize>(() => {
  if (typeof props.size === 'string') {
    return props.size as AvatarSize;
  }
  return mapPixelToSize(props.size);
});

const nameTextClass = computed(() => {
  const sizeMap: Record<AvatarSize, string> = {
    xxs: 'text-xs',
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

const photoEndpoint = computed(() => props.user.id ? route('api.v1.admin.users.photo', { user: props.user.id }) : '');
const { data: preview, execute: fetchPhoto, isFetching: photoFetching } = useApi<{ image: ImageData | null }>(photoEndpoint, { immediate: false, showErrorToast: false });
let fetchedPhotoFor: string | number | null = null;
async function loadPhoto(open: boolean) {
  if (!open || !canLinkUsers.value || !props.user.id || photoFetching.value || fetchedPhotoFor === props.user.id) return;
  fetchedPhotoFor = props.user.id;
  await fetchPhoto();
}

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

const pronounsLabel = computed(() => {
  const p = props.user.pronouns;
  if (Array.isArray(p)) return p.filter(Boolean).join('/');
  return typeof p === 'string' ? p : '';
});
</script>
