<template>
  <span v-if="!users.length">—</span>
  <ul v-else-if="users.length <= inlineLimit" data-slot="users-fact-list" class="flex flex-col gap-1.5">
    <li v-for="user in users" :key="user.id" class="flex min-w-0 items-center gap-2">
      <UserAvatar :user :size="24" class="shrink-0" />
      <span class="min-w-0 truncate">{{ user.name }}</span>
    </li>
  </ul>
  <UsersAvatarGroup v-else :users :max="5" :size="24" expandable />
</template>

<script setup lang="ts">
import UserAvatar from './UserAvatar.vue';
import UsersAvatarGroup from './UsersAvatarGroup.vue';

/** People in a record's key-facts strip: named when few, a stack that expands when many. */
withDefaults(defineProps<{
  users: App.Entities.User[];
  inlineLimit?: number;
}>(), {
  inlineLimit: 3,
});
</script>
