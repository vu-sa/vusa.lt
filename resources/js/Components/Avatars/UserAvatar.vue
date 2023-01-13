<template>
  <div class="inline-flex flex-row items-center">
    <NAvatar
      round
      :size="size"
      object-fit="cover"
      :src="user.profile_photo_path"
    >
      <span v-if="!user.profile_photo_path">
        {{ userInitials(user.name) }}
      </span>
    </NAvatar>
    <span v-if="showName" class="ml-2" :class="{ 'font-bold': bold }"
      >{{ user.name }}
      <template v-if="showPadalinys"
        >({{ user.padaliniai ?? "Be padalinio" }})</template
      ></span
    >
  </div>
</template>

<script setup lang="ts">
import { NAvatar } from "naive-ui";

withDefaults(
  defineProps<{
    bold?: boolean;
    showName?: boolean;
    showPadalinys?: boolean;
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
