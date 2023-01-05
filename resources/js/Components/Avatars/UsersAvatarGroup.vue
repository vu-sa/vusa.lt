<template>
  <NAvatarGroup :options="options" :max="4" :size="size ?? 40">
    <template #avatar="{ option: { name, src } }">
      <NAvatar v-if="src" object-fit="cover" :src="src">
        <!-- ! Doesn't work, for some reason -->
        <!-- <template #placeholder>
          <div class="flex h-full w-full items-center justify-center">
            <div class="my-auto">{{ userInitials(name) }}</div>
          </div>
        </template> -->
        <template #fallback>
          <div class="flex h-full w-full items-center justify-center">
            <div class="my-auto">{{ userInitials(name) }}</div>
          </div>
        </template>
      </NAvatar>
      <NAvatar v-else>{{ userInitials(name) }}</NAvatar>
    </template>
  </NAvatarGroup>
</template>

<script setup lang="tsx">
import { NAvatar, NAvatarGroup, NSpin } from "naive-ui";

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

const renderFallback = (name: string) => {
  return () => (
    <div class="flex h-full w-full items-center justify-center">
      <div class="my-auto">{userInitials(name)}</div>
    </div>
  );
};
</script>
