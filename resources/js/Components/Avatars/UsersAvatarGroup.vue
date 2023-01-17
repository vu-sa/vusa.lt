<template>
  <NAvatarGroup :options="options" :max="4" :size="size ?? 40">
    <template #avatar="{ option: option }">
      <UserPopover :user="option" />
    </template>
  </NAvatarGroup>
</template>

<script setup lang="tsx">
import { NAvatarGroup } from "naive-ui";
import UserPopover from "./UserPopover.vue";

const props = defineProps<{
  users: App.Entities.User[];
  size?: number;
}>();

const options = props.users.map((user: App.Entities.User) => {
  // return rest and map src and name
  return {
    ...user,
    name: user.name,
    src: user.profile_photo_path,
  };
});
</script>
