<template>
  <div class="not-prose inline-flex flex-row items-center">
    <NAvatar
      round
      :size="size"
      object-fit="cover"
      :src="user.profile_photo_path"
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
    <span v-if="showName" class="ml-2" :class="{ 'font-bold': bold }"
      >{{ user.name }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { NAvatar } from "naive-ui";

withDefaults(
  defineProps<{
    bold?: boolean;
    showName?: boolean;
    size?: string | number;
    user: App.Entities.User;
  }>(),
  {
    bold: false,
    size: "small",
  }
);

const userInitials = (name: string) => {
  const words = name.split(" ");
  return words[0].charAt(0) + words[words.length - 1].charAt(0);
};
</script>
