<template>
  <NAvatarGroup :options="options" :max="max ?? 4" :size="size ?? 40">
    <template #avatar="{ option: option }">
      <UserPopover :user="option" />
    </template>
    <template #rest="{ options: restOptions, rest }">
      <NPopover>
        <div class="flex flex-col justify-center gap-2">
          <UserPopover
            v-for="user in restOptions"
            :key="user.name"
            show-name
            :size="20"
            :user="user"
          />
        </div>
        <template #trigger
          ><NAvatar>+{{ rest }}</NAvatar></template
        >
      </NPopover>
    </template>
  </NAvatarGroup>
</template>

<script setup lang="tsx">
import { NAvatar, NAvatarGroup, NPopover } from "naive-ui";
import UserPopover from "./UserPopover.vue";

const props = defineProps<{
  users: App.Entities.User[];
  max?: number;
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
