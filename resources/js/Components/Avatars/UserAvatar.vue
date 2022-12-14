<template>
  <div class="inline-flex flex-row items-center">
    <NAvatar
      round
      size="small"
      object-fit="cover"
      :src="user.profile_photo_path"
    >
      <span v-if="!user.profile_photo_path">
        {{ userInitials(user.name) }}
      </span>
    </NAvatar>
    <span v-if="showName" class="ml-2"
      >{{ user.name }}
      <template v-if="showPadalinys"
        >({{ user.padalinys ?? "Be padalinio" }})</template
      ></span
    >
  </div>
</template>

<script setup lang="ts">
import { NAvatar } from "naive-ui";

defineProps<{
  showName?: boolean;
  showPadalinys?: boolean;
  user: App.Models.User;
}>();

const userInitials = (name: string) => {
  const words = name.split(" ");
  return words[0].charAt(0) + words[words.length - 1].charAt(0);
};
</script>
