<template>
  <NPopover class="rounded-sm" :show-arrow="false" raw style="max-width: 150px">
    <template #trigger>
      <UserAvatar v-if="!showName" :user="user" :size="size"></UserAvatar>
      <div v-else class="flex items-center gap-2">
        <UserAvatar :user="user" :size="size"></UserAvatar>
        <span>{{ user.name }}</span>
      </div>
    </template>
    <img v-if="photo" class="w-full rounded-t-sm" :src="photo" alt="" />
    <div
      class="flex flex-col gap-2 rounded-sm bg-white p-2 text-xs text-zinc-500"
    >
      <span class="font-bold text-zinc-800">{{ user.name }}</span>
      <a v-if="user.phone" :href="`tel:${user.phone}`">{{ user.phone }}</a>
    </div>
  </NPopover>
</template>

<script setup lang="ts">
import { NPopover } from "naive-ui";
import { computed } from "vue";
import UserAvatar from "./UserAvatar.vue";

const props = defineProps<{
  showName?: boolean;
  size?: number;
  user: Record<string, any>;
}>();

const photo = computed(() => {
  if (props.user.src) return props.user.src;
  if (props.user.profile_photo_path) return props.user.profile_photo_path;
  return null;
});
</script>
