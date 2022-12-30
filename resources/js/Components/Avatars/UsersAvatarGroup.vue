<template>
  <NAvatarGroup :options="options" :max="4" :size="size ?? 40">
    <template #avatar="{ option: { name, src } }">
      <NAvatar v-if="src" object-fit="cover" :src="src" />
      <NAvatar v-else>{{ userInitials(name) }}</NAvatar>
    </template>
  </NAvatarGroup>
</template>

<script setup lang="ts">
import { NAvatar, NAvatarGroup } from "naive-ui";

const props = defineProps<{
  users: Record<string, any>;
  size?: number;
}>();

const options = props.users.map((user: Record<string, any>) => ({
  name: user.name,
  src: user.profile_photo_path,
}));

const userInitials = (name: string) => {
  if (name === undefined) {
    return;
  }

  const words = name.split(" ");
  return words[0].charAt(0) + words[words.length - 1].charAt(0);
};
</script>
