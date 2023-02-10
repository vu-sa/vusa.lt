<template>
  <NAvatarGroup :options="options" :max="max ?? 4" :size="size ?? 40">
    <template #avatar="{ option: option }">
      <UserPopover :user="option" />
    </template>
    <template #rest="{ options: restOptions, rest }">
      <NDropdown
        :options="createRestDropdownOptions(restOptions)"
        placement="top"
      >
        <NAvatar>+{{ rest }}</NAvatar>
      </NDropdown>
    </template>
  </NAvatarGroup>
</template>

<script setup lang="tsx">
import { NAvatar, NAvatarGroup, NDropdown } from "naive-ui";
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

const createRestDropdownOptions = (options: Array<{ name: string }>) => {
  return options.map((option) => {
    return {
      label() {
        return <UserPopover showName size={28} user={option} />;
      },
      value: option.name,
    };
  });
};
</script>
