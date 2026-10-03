<template>
  <div class="relative inline-flex leading-none" :class="containerClass">
    <Avatar
      :size="avatarSize"
      :interactive
      :class="[
        'transition-all duration-200',
        border ? 'ring-1 ring-border' : ''
      ]"
    >
      <AvatarImage
        v-if="user?.profile_photo_path"
        :src="user.profile_photo_path"
        :alt="user.name ?? 'Profile photo'"
        class="object-cover"
        :style="focalPointStyle"
      />
      <AvatarFallback
        v-if="user"
        :class="[
          'font-medium transition-colors',
          textSizeClass
        ]"
      >
        {{ userInitials(user.name) }}
      </AvatarFallback>
    </Avatar>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { Avatar, AvatarImage, AvatarFallback, mapPixelToSize, avatarTextSizes, type AvatarSize } from '@/Components/ui/avatar';

const props = defineProps<{
  user?: App.Entities.User;
  size?: number | AvatarSize;
  interactive?: boolean;
  border?: boolean;
  class?: string;
}>();

const containerClass = computed(() => props.class || '');

/** Keeps the subject in frame on portraits cropped to a circle. */
const focalPointStyle = computed(() =>
  props.user?.profile_photo_focal_point
    ? { objectPosition: props.user.profile_photo_focal_point }
    : undefined,
);

// Support both pixel values (backward compat) and size variant names
const avatarSize = computed<AvatarSize>(() => {
  if (typeof props.size === 'string') {
    return props.size as AvatarSize;
  }
  return mapPixelToSize(props.size);
});

const textSizeClass = computed(() => {
  return avatarTextSizes[avatarSize.value];
});

const userInitials = (name: string | null) => {
  if (!name) return '';

  const words = name.split(' ');
  if (words.length === 1) return words[0].substring(0, 2).toUpperCase();
  return (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
};
</script>
