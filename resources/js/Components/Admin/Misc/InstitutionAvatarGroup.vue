<template>
  <NAvatarGroup :options="options" :max="4" :size="40"></NAvatarGroup>
</template>

<script setup lang="ts">
import { NAvatarGroup } from "naive-ui";

const props = defineProps<{
  users: Record<string, any>;
}>();

const options = props.users.map((user: Record<string, any>) => ({
  name: user.name,
  src: user.profile_photo_path,
}));
</script>
