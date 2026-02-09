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
        alt="Profile photo"
        class="object-cover"
      />
      <AvatarFallback
        v-if="user"
        :class="[
          'text-foreground font-medium transition-colors',
          textSizeClass
        ]"
      >
        {{ userInitials(user.name) }}
      </AvatarFallback>
    </Avatar>

    <!-- Status indicator -->
    <!-- <div
      v-if="status"
      class="absolute bottom-0 right-0 transform translate-x-1/4 translate-y-1/4 rounded-full ring-2 ring-background"
      :class="[
        statusSizeClass,
        status === 'online' ? 'bg-success' :
        status === 'away' ? 'bg-warning' :
        status === 'busy' ? 'bg-destructive' :
        status === 'offline' ? 'bg-muted' : ''
      ]"
      aria-hidden="true"
    ></div> -->
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { Avatar, AvatarImage, AvatarFallback, mapPixelToSize, avatarTextSizes, type AvatarSize } from '@/Components/ui/avatar';

type StatusType = 'online' | 'offline' | 'away' | 'busy' | null;

const props = defineProps<{
  user?: App.Entities.User;
  size?: number | AvatarSize;
  interactive?: boolean;
  border?: boolean;
  status?: StatusType;
  class?: string;
}>();

const containerClass = computed(() => props.class || '');

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

const statusSizeClass = computed(() => {
  const sizeMap: Record<AvatarSize, string> = {
    xs: 'size-1.5',
    sm: 'size-2',
    default: 'size-2.5',
    lg: 'size-3',
    xl: 'size-3',
  };
  return sizeMap[avatarSize.value];
});

const userInitials = (name: string | null) => {
  if (!name) return '';

  const words = name.split(' ');
  if (words.length === 1) return words[0].substring(0, 2).toUpperCase();
  return (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
};
</script>
