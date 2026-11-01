<template>
  <ul v-if="users.length <= NAMED_LIMIT" class="flex flex-col gap-1" data-slot="representatives-list">
    <li v-for="user in users" :key="user.id">
      <UserPopover :user show-name :size="28" clickable />
    </li>
  </ul>
  <UsersAvatarGroup v-else :users :max="10" :size="32" expandable clickable />
</template>

<script setup lang="ts">
import UserPopover from './UserPopover.vue';
import UsersAvatarGroup from './UsersAvatarGroup.vue';

/** A few people read best by name; an avatar group only earns its place past that. */
const NAMED_LIMIT = 5;

defineProps<{
  users: App.Entities.User[];
}>();
</script>
