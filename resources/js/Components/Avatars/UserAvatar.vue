<template>
  <NAvatar
    v-if="user"
    :size="size"
    round
    object-fit="cover"
    :src="user.profile_photo_path ?? undefined"
  >
    <span v-if="!user.profile_photo_path">
      {{ userInitials(user.name) }}
    </span>
    <template #fallback>
      <div class="flex h-full w-full items-center justify-center">
        <div class="my-auto">{{ userInitials(user.name) }}</div>
      </div>
    </template>
  </NAvatar>
</template>

<script setup lang="tsx">
import { NAvatar } from "naive-ui";

defineProps<{
  user?: App.Entities.User;
  size?: number;
}>();

const userInitials = (name: string | null) => {
  if (!name) return "";

  const words = name.split(" ");
  return words[0].charAt(0) + words[words.length - 1].charAt(0);
};
</script>
